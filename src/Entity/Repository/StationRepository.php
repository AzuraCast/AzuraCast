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
    protected Media $media_sync;

    protected Adapters $adapters;

    protected Configuration $configuration;

    protected ValidatorInterface $validator;

    protected CacheInterface $cache;

    protected SettingsRepository $settingsRepo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Media $media_sync,
        Adapters $adapters,
        Configuration $configuration,
        ValidatorInterface $validator,
        CacheInterface $cache
    ) {
        $this->media_sync = $media_sync;
        $this->adapters = $adapters;
        $this->configuration = $configuration;
        $this->validator = $validator;
        $this->cache = $cache;
        $this->settingsRepo = $settingsRepo;

        parent::__construct($em, $serializer, $settings, $logger);
    }

    /**
     * @param string $identifier A numeric or string identifier for a station.
     *
     * @return Entity\Station|null
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
     * @return array
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
     *
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        return $this->repository->findOneBy(['short_name' => $short_code]);
    }

    /**
     * @param Entity\Station $record
     *
     * @return Entity\Station
     */
    public function edit(Entity\Station $record): Entity\Station
    {
        $original_record = $this->em->getUnitOfWork()->getOriginalEntityData($record);

        // Get the original values to check for changes.
        $old_frontend = $original_record['frontend_type'];
        $old_backend = $original_record['backend_type'];

        $frontend_changed = ($old_frontend !== $record->getFrontendType());
        $backend_changed = ($old_backend !== $record->getBackendType());
        $adapter_changed = $frontend_changed || $backend_changed;

        if ($frontend_changed) {
            $frontend = $this->adapters->getFrontendAdapter($record);
            $this->resetMounts($record, $frontend);
        }

        $this->configuration->writeConfiguration($record, $adapter_changed);

        $this->cache->delete('stations');

        return $record;
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
    }

    /**
     * Handle tasks necessary to a station's creation.
     *
     * @param Entity\Station $station
     *
     * @return Entity\Station
     */
    public function create(Entity\Station $station): Entity\Station
    {
        // Create path for station.
        $station->setRadioBaseDir(null);

        $this->em->persist($station);

        // Generate station ID.
        $this->em->flush();

        // Scan directory for any existing files.
        set_time_limit(600);
        $this->media_sync->importMusic($station);

        /** @var Entity\Station $station */
        $station = $this->em->find(Entity\Station::class, $station->getId());

        $this->media_sync->importPlaylists($station);

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
     *
     * @return UriInterface
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
