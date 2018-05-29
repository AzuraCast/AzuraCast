<?php
namespace Entity\Repository;

use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Configuration;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Entity;

class StationRepository extends BaseRepository
{
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
        foreach ((array)$results as $result) {
            $key = $result[$pk];
            $value = ($display === null) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    /**
     * @param $short_code
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        return $this->findOneBy(['short_name' => $short_code]);
    }

    /**
     * Create a station based on the specified data.
     *
     * @param array $data Array of data to populate the station with.
     * @param Adapters $adapters
     * @param Configuration $configuration
     * @return Entity\Station
     * @throws \Exception
     */
    public function create($data, Adapters $adapters, Configuration $configuration)
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
        $media_sync = new \AzuraCast\Sync\Task\Media($this->_em);

        set_time_limit(600);
        $media_sync->importMusic($station);
        $this->_em->refresh($station);

        $media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load adapters.
        $frontend_adapter = $adapters->getFrontendAdapter($station);
        $backend_adapter = $adapters->getBackendAdapter($station);

        // Create default mountpoints if station supports them.
        $this->resetMounts($station, $frontend_adapter);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter->read();

        // Write the adapter configurations and update supervisord.
        $configuration->writeConfiguration($station, true);

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();

        return $station;
    }

    /**
     * Reset mount points to their adapter defaults (in the event of an adapter change).
     *
     * @param Entity\Station $station
     * @param FrontendAbstract $frontend_adapter
     */
    public function resetMounts(Entity\Station $station, FrontendAbstract $frontend_adapter)
    {
        foreach($station->getMounts() as $mount) {
            $this->_em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($frontend_adapter->supportsMounts()) {
            // Create default mount points.
            $mount_points = $frontend_adapter->getDefaultMounts();

            foreach ($mount_points as $mount_point) {
                $mount_record = new Entity\StationMount($station);
                $this->fromArray($mount_record, $mount_point);

                $this->_em->persist($mount_record);
            }

            $this->_em->flush();
            $this->_em->refresh($station);
        }
    }

    /**
     * @param Entity\Station $station
     * @param Adapters $adapters
     * @param Configuration $configuration
     * @throws \Exception
     */
    public function destroy(Entity\Station $station, Adapters $adapters, Configuration $configuration)
    {
        $configuration->removeConfiguration($station);

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        \App\Utilities::rmdir_recursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->_em->remove($station);
        $this->_em->flush();
    }
}