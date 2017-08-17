<?php
namespace Entity\Repository;

use Entity;
use Interop\Container\ContainerInterface;

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
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC')
    {
        $stations = parent::fetchArray($cached, $order_by, $order_dir);
        foreach ($stations as &$station) {
            $station['short_name'] = Entity\Station::getStationShortName($station['name']);
        }

        return $stations;
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
     * @param bool $cached
     * @return array
     */
    public function getShortNameLookup($cached = true)
    {
        $stations = $this->fetchArray($cached);

        $lookup = [];
        foreach ($stations as $station) {
            $lookup[$station['short_name']] = $station;
        }

        return $lookup;
    }

    /**
     * @param $short_code
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        $short_names = $this->getShortNameLookup();

        if (isset($short_names[$short_code])) {
            $id = $short_names[$short_code]['id'];

            return $this->find($id);
        }

        return null;
    }

    /**
     * Create a station based on the specified data.
     *
     * @param $data
     * @param ContainerInterface $di
     * @return Entity\Station
     */
    public function create($data, ContainerInterface $di)
    {
        $station = new Entity\Station;
        $this->fromArray($station, $data);

        // Create path for station.
        $station_base_dir = realpath(APP_INCLUDE_ROOT . '/..') . '/stations';

        $station_dir = $station_base_dir . '/' . $station->getShortName();
        $station->setRadioBaseDir($station_dir);

        $this->_em->persist($station);

        // Generate station ID.
        $this->_em->flush();

        // Scan directory for any existing files.
        $media_sync = new \AzuraCast\Sync\Media($di);

        set_time_limit(600);
        $media_sync->importMusic($station);
        $this->_em->refresh($station);

        $media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load adapters.
        $frontend_adapter = $station->getFrontendAdapter($di);
        $backend_adapter = $station->getBackendAdapter($di);

        // Create default mountpoints if station supports them.
        $this->resetMounts($station, $di);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter->read();

        // Write the adapter configurations and update supervisord.
        $station->writeConfiguration($di);

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();

        return $station;
    }

    /**
     * Reset mount points to their adapter defaults (in the event of an adapter change).
     *
     * @param Entity\Station $station
     * @param ContainerInterface $di
     */
    public function resetMounts(Entity\Station $station, ContainerInterface $di)
    {
        foreach($station->getMounts() as $mount) {
            $this->_em->remove($mount);
        }

        $frontend_adapter = $station->getFrontendAdapter($di);

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
     * @param ContainerInterface $di
     */
    public function destroy(Entity\Station $station, ContainerInterface $di)
    {
        $frontend = $station->getFrontendAdapter($di);
        $backend = $station->getBackendAdapter($di);

        if ($frontend->hasCommand() || $backend->hasCommand()) {
            /** @var \Supervisor\Supervisor $supervisor */
            $supervisor = $di['supervisor'];

            $frontend_name = $frontend->getProgramName();
            list($frontend_group, $frontend_program) = explode(':', $frontend_name);

            $supervisor->stopProcessGroup($frontend_group);
        }

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        \App\Utilities::rmdir_recursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->_em->remove($station);
        $this->_em->flush();
    }
}