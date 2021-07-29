<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Assets\AssetFactory;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Radio\Adapters;
use App\Radio\Configuration;
use App\Radio\Frontend\AbstractFrontend;
use App\Utilities;
use Closure;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends Repository<Entity\Station>
 */
class StationRepository extends Repository
{
    public function __construct(
        protected SettingsRepository $settingsRepo,
        protected StorageLocationRepository $storageLocationRepo,
        protected LoggerInterface $logger,
        protected Adapters $adapters,
        protected Configuration $configuration,
        protected ValidatorInterface $validator,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
    ) {
        parent::__construct($em, $serializer, $environment, $logger);
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
     */
    public function fetchAll(): mixed
    {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT s FROM App\Entity\Station s ORDER BY s.name ASC
            DQL
        )->execute();
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
        // Assemble select values and, if necessary, call $display callback.
        foreach ($this->fetchArray() as $result) {
            $key = $result[$pk];
            $select[$key] = ($display === null) ? $result['name'] : $display($result);
        }

        return $select;
    }

    /**
     * @param string $short_code
     */
    public function findByShortCode(string $short_code): ?Entity\Station
    {
        return $this->repository->findOneBy(['short_name' => $short_code]);
    }

    /**
     * @param Entity\Station $station
     */
    public function edit(Entity\Station $station): Entity\Station
    {
        $original_record = $this->em->getUnitOfWork()->getOriginalEntityData($station);

        $this->configuration->initializeConfiguration($station);

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

        if ($adapter_changed) {
            $this->configuration->writeConfiguration($station, true);
        }

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
        if ($frontend_adapter->supportsMounts()) {
            // Create default mount points.
            foreach ($frontend_adapter->getDefaultMounts() as $mount_point) {
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
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\SongHistory sh SET sh.media = null
                WHERE sh.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist_id IN (
                    SELECT sp.id FROM App\Entity\StationPlaylist sp WHERE sp.station = :station
                )
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq WHERE sq.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationRequest sr WHERE sr.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();
    }

    /**
     * Handle tasks necessary to a station's creation.
     *
     * @param Entity\Station $station
     */
    public function create(Entity\Station $station): Entity\Station
    {
        $station->generateAdapterApiKey();

        $this->configuration->initializeConfiguration($station);

        // Create default mountpoints if station supports them.
        $frontend_adapter = $this->adapters->getFrontendAdapter($station);
        $this->resetMounts($station, $frontend_adapter);

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
        Utilities\File::rmdirRecursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->em->flush();

        foreach ($station->getAllStorageLocations() as $storageLocation) {
            $stations = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
            if (1 === count($stations)) {
                $this->em->remove($storageLocation);
            }
        }

        $this->em->remove($station);
        $this->em->flush();
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @param Entity\Station|null $station
     */
    public function getDefaultAlbumArtUrl(?Entity\Station $station = null): UriInterface
    {
        if (null !== $station) {
            $stationCustomUrl = trim($station->getDefaultAlbumArtUrl() ?? '');

            if (!empty($stationCustomUrl)) {
                return new Uri($stationCustomUrl);
            }
        }

        $settings = $this->settingsRepo->readSettings();
        $custom_url = trim($settings->getDefaultAlbumArtUrl() ?? '');

        if (!empty($custom_url)) {
            return new Uri($custom_url);
        }

        $systemAlbumArt = AssetFactory::createAlbumArt($this->environment);
        return new Uri($systemAlbumArt->getUrl());
    }
}
