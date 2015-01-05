<?php
namespace Modules\Frontend\Controllers;

use \Entity\Station;
use \Entity\Convention;
use \Entity\Settings;

class SpecialController extends BaseController
{
    public function indexAction()
    {
        $default_station = 'PVL Presents (Video)';
        $stations_covering = array(
            'PVL Presents (Video)' => 'Video Stream',
            'PVL Presents (Radio)' => 'Radio Stream',
        );

        $categories = array(
            'event' => array(
                'name' => 'Live Event Coverage',
                'icon' => 'icon-star',
                'stations' => array(),
            ),
        );

        $all_stations = Station::fetchArray();
        $stations_by_name = array();
        foreach($all_stations as $station)
        {
            $name = $station['name'];
            $stations_by_name[$name] = $station;

            if (isset($stations_covering[$name]))
            {
                $station['category'] = 'event';
                $station['nickname'] = $stations_covering[$name];
                
                $categories['event']['stations'][] = $station;
            }
        }

        $this->view->categories = $categories;
        $this->view->station_id = $stations_by_name[$default_station]['id'];
        $this->view->autoplay = true;
    }

    public function galaconAction()
    {
        $con = Convention::getRepository()->findOneBy(array('name' => 'GalaCon 2014'));
        $this->view->convention = $con;

        $this->view->special_event_embed = trim(Settings::getSetting('special_event_embed_code'));
        $this->view->special_chat_embed = trim(Settings::getSetting('special_event_chat_code'));
    }

    public function fiestaAction()
    {
        $con = Convention::getRepository()->findOneBy(array('name' => 'Fiesta Equestria 2013'));
        $this->redirectToRoute(array('module' => 'default', 'controller' => 'convention', 'action' => 'archive', 'id' => $con->id));
    }

    public function cddAction()
    {
        $this->redirectHome();
    }
}
