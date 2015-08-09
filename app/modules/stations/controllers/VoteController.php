<?php
namespace Modules\Stations\Controllers;

use \Entity\Station;
use \Entity\StationManager;

class VoteController extends BaseController
{
    protected $vote_hash;
    protected $vote_name;

    public function preDispatch()
    {
        parent::preDispatch();

        $user = $this->auth->getLoggedInUser();

        $this->vote_hash = $this->station->id.'_'.md5(strtolower($user->email));
        $this->vote_name = $this->station->name.' ('.$user->name.')';
    }

    public function indexAction()
    {
        $pending_raw = $this->em->createQuery('SELECT s FROM Entity\Station s WHERE s.is_active = 0 ORDER BY s.id DESC')->getArrayResult();

        $pending_stations = array();
        foreach($pending_raw as $station)
        {
            $votes_raw = (array)$station['intake_votes'];

            if (isset($votes_raw[$this->vote_hash]))
                $station['my_vote'] = $votes_raw[$this->vote_hash];

            $pending_stations[$station['id']] = $station;
        }

        $this->view->pending_stations = $pending_stations;
    }

    public function viewAction()
    {
        $id = $this->getParam('id');
        $station = Station::find($id);

        if ($station->is_active)
            throw new \DF\Exception\DisplayOnly('This station has already been reviewed and is active.');

        $station_form = new \DF\Form($this->module_config['frontend']->forms->submit_station);
        $station_form->populate($station->toArray());

        $form = new \DF\Form($this->current_module_config->forms->vote);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();
            $data['name'] = $this->vote_name;

            $intake_votes = (array)$station->intake_votes;
            if ($data['decision'] == 'Abstain')
                unset($intake_votes[$this->vote_hash]);
            else
                $intake_votes[$this->vote_hash] = $data;

            $station->intake_votes = $intake_votes;
            $station->save();

            $this->alert('Your vote has been submitted. Thank you for your feedback.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->view->station_form = $station_form;
        $this->view->form = $form;
    }
}