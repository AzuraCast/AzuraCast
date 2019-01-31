<?php
namespace App\Entity\Repository;

use App\Radio\Adapters;
use App\Radio\Configuration;
use App\Radio\Frontend\AbstractFrontend;
use App\Entity;
use App\Sync\Task\Media;
use Azura\Cache;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;

class StationRepository extends Repository
{
    /** @var Media */
    protected $media_sync;

    /** @var Adapters */
    protected $adapters;

    /** @var Configuration */
    protected $configuration;

    /** @var Cache */
    protected $cache;

    public function __construct(
        $em,
        Mapping\ClassMetadata $class,
        Media $media_sync,
        Adapters $adapters,
        Configuration $configuration,
        Cache $cache
    ) {
        parent::__construct($em, $class);

        $this->media_sync = $media_sync;
        $this->adapters = $adapters;
        $this->configuration = $configuration;
        $this->cache = $cache;
    }

    /**
     * @return mixed
     */
    public function fetchAll()
    {
        return $this->_em->createQuery('SELECT s FROM ' . $this->_entityName . ' s ORDER BY s.name ASC')
            ->execute();
    }

    /**
     * @param bool $add_blank
     * @param \Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     * @return array
     */
    public function fetchSelect($add_blank = false, \Closure $display = null, $pk = 'id', $order_by = 'name')
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
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        return $this->findOneBy(['short_name' => $short_code]);
    }

    /**
     * @param array $data
     * @param Entity\Station|null $record
     * @return Entity\Station
     */
    public function editOrCreate($data, Entity\Station $record = null): Entity\Station
    {
        return (null === $record)
            ? $this->create($data)
            : $this->edit($data, $record);
    }

    /**
     * @param array $data
     * @param Entity\Station $record
     * @return Entity\Station
     */
    public function edit($data, Entity\Station $record): Entity\Station
    {
        $old_frontend = $record->getFrontendType();
        $old_backend = $record->getBackendType();

        $this->fromArray($record, $data);
        $this->_em->persist($record);
        $this->_em->flush($record);

        $frontend_changed = ($old_frontend !== $record->getFrontendType());
        $backend_changed = ($old_backend !== $record->getBackendType());
        $adapter_changed = $frontend_changed || $backend_changed;

        if ($frontend_changed) {
            $frontend = $this->adapters->getFrontendAdapter($record);
            $this->resetMounts($record, $frontend);
        }

        $this->configuration->writeConfiguration($record, $adapter_changed);

        $this->cache->remove('stations');

        return $record;
    }

    /**
     * Create a station based on the specified data.
     *
     * @param array $data Array of data to populate the station with.
     * @return Entity\Station
     * @throws \Exception
     */
    public function create($data): Entity\Station
    {
        $station = new Entity\Station;
        $this->fromArray($station, $data);

        // Create path for station.
        $station_base_dir = dirname(APP_INCLUDE_ROOT) . '/stations';

        $station_dir = $station_base_dir . '/' . $station->getShortName();
        $station->setRadioBaseDir($station_dir);

        $this->_em->persist($station);

        // Generate station ID.
        $this->_em->flush();

        // Scan directory for any existing files.
        set_time_limit(600);
        $this->media_sync->importMusic($station);
        $this->_em->refresh($station);

        $this->media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load adapters.
        $frontend_adapter = $this->adapters->getFrontendAdapter($station);
        $backend_adapter = $this->adapters->getBackendAdapter($station);

        // Create default mountpoints if station supports them.
        $this->resetMounts($station, $frontend_adapter);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter->read($station);

        // Write the adapter configurations and update supervisord.
        $this->configuration->writeConfiguration($station, true);

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();

        $this->cache->remove('stations');

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
        foreach($station->getMounts() as $mount) {
            $this->_em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($frontend_adapter::supportsMounts()) {
            // Create default mount points.
            $mount_points = $frontend_adapter::getDefaultMounts();

            foreach ($mount_points as $mount_point) {
                $mount_record = new Entity\StationMount($station);
                $this->fromArray($mount_record, $mount_point);

                $this->_em->persist($mount_record);
            }
        }

        $this->_em->flush();
        $this->_em->refresh($station);
    }

    /**
     * @param Entity\Station $station
     * @throws \Exception
     */
    public function destroy(Entity\Station $station): void
    {
        $this->configuration->removeConfiguration($station);

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        \App\Utilities::rmdirRecursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->_em->remove($station);
        $this->_em->flush($station);

        $this->cache->remove('stations');
    }

    /**
     * @param mixed|null $port
     * @param Entity\Station|null $except_record
     * @return bool
     */
    public function isPortUsed($port, Entity\Station $except_record = null): bool
    {
        if (!empty($port)) {
            $port = (int)$port;
            $used_ports = $this->configuration->getUsedPorts($except_record);

            return isset($used_ports[$port]);
        }

        return false;
    }
}
