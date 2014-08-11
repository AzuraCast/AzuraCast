<?php
namespace PVL\Controller\Action;

class Station extends \DF\Controller\Action
{
    protected $stations;
    protected $station;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->forceSecure();

        $user = $this->auth->getLoggedInUser();

        // Compile list of visible stations.
        $all_stations = \Entity\Station::fetchAll();
        $stations = array();
        
        foreach($all_stations as $station)
        {
            if ($station->canManage($user))
                $stations[$station->id] = $station;
        }

        $this->stations = $stations;
        $this->view->stations = $stations;

        // Assign a station if one is selected.
        if ($this->_hasParam('station'))
        {
            $station_id = (int)$this->_getParam('station');
            if (isset($stations[$station_id]))
            {
                $this->station = $stations[$station_id];
                $this->view->station = $this->station;
            }
            else
            {
                throw new \DF\Exception\PermissionDenied;
            }
        }
        else if (count($this->stations) == 1)
        {
            // Convenience auto-redirect for single-station admins.
            $this->redirectFromHere(array('station' => key($this->stations)));
            return;
        }

        // Force a redirect to the "Select" page if no station ID is specified.
        if (!$this->station && $this->_getActionName() != 'select')
        {
            $this->redirectToRoute(array('module' => 'stations', 'controller' => 'index', 'action' => 'select', 'station' => NULL));
            return;
        }
    }

    public function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }
}