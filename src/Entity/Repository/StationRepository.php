<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Radio\Adapters;
use App\Radio\Configuration;
use App\Radio\Frontend\AbstractFrontend;
use App\Settings;
use App\Sync\Task\Media;
use App\Utilities;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationRepository extends Repository
{
    protected Media $mediaSync;

    protected Adapters $adapters;

    protected Configuration $configuration;

    protected ValidatorInterface $validator;

    protected CacheInterface $cache;

    protected SettingsRepository $settingsRepo;

    protected StorageLocationRepository $storageLocationRepo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        SettingsRepository $settingsRepo,
        StorageLocationRepository $storageLocationRepo,
        LoggerInterface $logger,
        Media $mediaSync,
        Adapters $adapters,
        Configuration $configuration,
        ValidatorInterface $validator,
        CacheInterface $cache
    ) {
        $this->mediaSync = $mediaSync;
        $this->adapters = $adapters;
        $this->configuration = $configuration;
        $this->validator = $validator;
        $this->cache = $cache;
        $this->settingsRepo = $settingsRepo;
        $this->storageLocationRepo = $storageLocationRepo;

        parent::__construct($em, $serializer, $settings, $logger);
    }

    /**
     * @param string $identifier A numeric or string identifier for a station.
     */
    public function findByIdentifier(string $identifier): ?Entity\Station
    {
        return is_numeric($identifier)
            ? $this->repository->find($identifier)
            : $this->repository->findOneBy(['short_name' => $identifier]);
    }

    /**
     * @return mixed
     */
    public function fetchAll()
    {
        return $this->em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s ORDER BY s.name ASC')
            ->execute();
    }

    /**
     * @param bool|string $add_blank
     * @param Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     *
     * @return mixed[]
     */
    public function fetchSelect($add_blank = false, Closure $display = null, $pk = 'id', $order_by = 'name'): array
    {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? 'Select...' : $add_blank;
        }

        // Build query for records.
        $results = $this->fetchArray();

        // Assemble select values and, if necessary, call $display callback.
        foreach ($results as $result) {
            $key = $result[$pk];
            $value = ($display === null) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    /**
     * @param string $short_code
     */
    public function findByShortCode($short_code): ?Entity\Station
    {
        return $this->repository->findOneBy(['short_name' => $short_code]);
    }

    /**
     * @param Entity\Station $station
     */
    public function edit(Entity\Station $station): Entity\Station
    {
        // Create path for station.
        $station->ensureDirectoriesExist();

        $this->em->persist($station);
        $this->em->persist($station->getMediaStorageLocation());
        $this->em->persist($station->getRecordingsStorageLocation());

        $original_record = $this->em->getUnitOfWork()->getOriginalEntityData($station);

        // Generate station ID.
        $this->em->flush();

        // Delete media-related items if the media storage is changed.
        /** @var Entity\StorageLocation|null $oldMediaStorage */
        $oldMediaStorage = $original_record['media_storage_location'];
        $newMediaStorage = $station->getMediaStorageLocation();

        if (null === $oldMediaStorage || $oldMediaStorage->getId() !== $newMediaStorage->getId()) {
            $this->flushRelatedMedia($station);
        }

        // Get the original values to check for changes.
        $old_frontend = $original_record['frontend_type'];
        $old_backend = $original_record['backend_type'];

        $frontend_changed = ($old_frontend !== $station->getFrontendType());
        $backend_changed = ($old_backend !== $station->getBackendType());
        $adapter_changed = $frontend_changed || $backend_changed;

        if ($frontend_changed) {
            $frontend = $this->adapters->getFrontendAdapter($station);
            $this->resetMounts($station, $frontend);
        }

        $this->configuration->writeConfiguration($station, $adapter_changed);

        $this->cache->delete('stations');

        return $station;
    }

    /**
     * Reset mount points to their adapter defaults (in the event of an adapter change).
     *
     * @param Entity\Station $station
     * @param AbstractFrontend $frontend_adapter
     */
    public function resetMounts(Entity\Station $station, AbstractFrontend $frontend_adapter): void
    {
        foreach ($station->getMounts() as $mount) {
            $this->em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($frontend_adapter::supportsMounts()) {
            // Create default mount points.
            $mount_points = $frontend_adapter::getDefaultMounts();

            foreach ($mount_points as $mount_point) {
                $mount_record = new Entity\StationMount($station);
                $this->fromArray($mount_record, $mount_point);

                $this->em->persist($mount_record);
            }
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    protected function flushRelatedMedia(Entity\Station $station): void
    {
        $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\SongHistory sh SET sh.media = null
            WHERE sh.station = :station')
            ->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationPlaylistMedia spm
            WHERE spm.playlist_id IN (SELECT sp.id FROM App\Entity\StationPlaylist sp WHERE sp.station = :station)')
            ->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationQueue sq
            WHERE sq.station = :station')
            ->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationRequest sr
            WHERE sr.station = :station')
            ->setParameter('station', $station)
            ->execute();
    }

    /**
     * Handle tasks necessary to a station's creation.
     *
     * @param Entity\Station $station
     */
    public function create(Entity\Station $station): Entity\Station
    {
        // Create path for station.
        $station->ensureDirectoriesExist();

        $this->em->persist($station);
        $this->em->persist($station->getMediaStorageLocation());
        $this->em->persist($station->getRecordingsStorageLocation());

        // Generate station ID.
        $this->em->flush();

        // Scan directory for any existing files.
        set_time_limit(600);
        $this->mediaSync->importMusic($station->getMediaStorageLocation());

        /** @var Entity\Station $station */
        $station = $this->em->find(Entity\Station::class, $station->getId());

        $this->mediaSync->importPlaylists($station);

        /** @var Entity\Station $station */
        $station = $this->em->find(Entity\Station::class, $station->getId());

        // Load adapters.
        $frontend_adapter = $this->adapters->getFrontendAdapter($station);

        // Create default mountpoints if station supports them.
        $this->resetMounts($station, $frontend_adapter);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter->read($station);

        // Write the adapter configurations and update supervisord.
        $this->configuration->writeConfiguration($station, true);

        // Save changes and continue to the last setup step.
        $this->em->persist($station);
        $this->em->flush();

        $this->cache->delete('stations');

        return $station;
    }

    /**
     * @param Entity\Station $station
     *
     * @throws Exception
     */
    public function destroy(Entity\Station $station): void
    {
        $this->configuration->removeConfiguration($station);

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        Utilities::rmdirRecursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->em->flush();

        $storageLocations = [
            $station->getMediaStorageLocation(),
            $station->getRecordingsStorageLocation(),
        ];

        foreach ($storageLocations as $storageLocation) {
            $stations = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
            if (1 === count($stations)) {
                $this->em->remove($storageLocation);
            }
        }

        $this->em->remove($station);
        $this->em->flush();

        $this->cache->delete('stations');
    }

    /**
     * Clear the now-playing cache from all stations.
     */
    public function clearNowPlaying(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\Station s SET s.nowplaying=null')
            ->execute();
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @param Entity\Station|null $station
     */
    public function getDefaultAlbumArtUrl(?Entity\Station $station = null): UriInterface
    {
        if ($station instanceof Entity\Station) {
            $stationCustomUrl = trim($station->getDefaultAlbumArtUrl());

            if (!empty($stationCustomUrl)) {
                return new Uri($stationCustomUrl);
            }
        }

        $custom_url = trim($this->settingsRepo->getSetting(Entity\Settings::DEFAULT_ALBUM_ART_URL));

        if (!empty($custom_url)) {
            return new Uri($custom_url);
        }

        return new Uri('/static/img/generic_song.jpg');
    }
}
