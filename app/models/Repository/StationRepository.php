<?php
namespace Repository;

use App\Doctrine\Repository;
use Entity\Station as Record;
use Interop\Container\ContainerInterface;
use Pimple\Container;

class StationRepository extends Repository
{
    /**
     * @return mixed
     */
    public function fetchAll()
    {
        return $this->_em->createQuery('SELECT s FROM '.$this->_entityName.' s ORDER BY s.name ASC')
            ->execute();
    }

    /**
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = NULL, $order_dir = 'ASC')
    {
        $stations = parent::fetchArray($cached, $order_by, $order_dir);
        foreach($stations as &$station)
            $station['short_name'] = Record::getStationShortName($station['name']);

        return $stations;
    }

    /**
     * @param bool $add_blank
     * @param \Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     * @return array
     */
    public function fetchSelect($add_blank = FALSE, \Closure $display = NULL, $pk = 'id', $order_by = 'name')
    {
        $select = array();

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== FALSE)
            $select[''] = ($add_blank === TRUE) ? 'Select...' : $add_blank;

        // Build query for records.
        $results = $this->fetchArray();

        // Assemble select values and, if necessary, call $display callback.
        foreach((array)$results as $result)
        {
            $key = $result[$pk];
            $value = ($display === NULL) ? $result['name'] : $display($result);
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

        $lookup = array();
        foreach ($stations as $station)
            $lookup[$station['short_name']] = $station;

        return $lookup;
    }

    /**
     * @param $short_code
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        $short_names = $this->getShortNameLookup();

        if (isset($short_names[$short_code]))
        {
            $id = $short_names[$short_code]['id'];
            return $this->find($id);
        }

        return NULL;
    }

    /**
     * @param $data
     */
    public function create($data, ContainerInterface $di)
    {
        $station = new Record;
        $station->fromArray($this->_em, $data);

        // Create path for station.
        $station_base_dir = realpath(APP_INCLUDE_ROOT.'/..').'/stations';

        $station_dir = $station_base_dir.'/'.$station->getShortName();
        $station->setRadioBaseDir($station_dir);

        // Generate station ID.
        $this->_em->persist($station);
        $this->_em->flush();

        // Scan directory for any existing files.
        $media_sync = new \App\Sync\Media($di);

        set_time_limit(600);
        $media_sync->importMusic($station);
        $this->_em->refresh($station);

        $media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter = $station->getFrontendAdapter();
        $frontend_adapter->read();

        // Write initial XML file (if it doesn't exist).
        $frontend_adapter->write();
        $frontend_adapter->restart();

        // Write an empty placeholder configuration.
        $backend_adapter = $station->getBackendAdapter();
        $backend_adapter->write();
        $backend_adapter->restart();

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();
    }
}