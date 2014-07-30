<?php
use \Entity\Convention as Record;
use \Entity\Convention;
use \Entity\ConventionSignup;
use \Entity\ConventionArchive;

class ConventionController extends \DF\Controller\Action
{
    protected function _getConvention($required = false)
    {
        $convention = null;
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $convention = Convention::find($id);
        }

        if ($convention instanceof Convention)
            return $convention;
        elseif ($required)
            throw new \DF\Exception\DisplayOnly('Convention not specified!');
        else
            return null;
    }

    // Public convention archives view.
    public function indexAction()
    {
        if ($this->hasParam('id'))
            $this->redirectFromHere(array('action' => 'archive'));

        // Pull conventions.
        $conventions = Convention::getAllConventions();
        $this->view->conventions_upcoming = $conventions['upcoming'];
        $this->view->conventions_archived = $conventions['archived'];

        $this->render();
    }

    public function archiveAction()
    {
        $convention = $this->_getConvention();

        if (!$convention)
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));

        $this->view->convention = $convention;

        $videos = array();
        $sources = array();
        $folders = ConventionArchive::getFolders();

        foreach($folders as $folder_key => $folder_name)
            $videos[$folder_name] = array('key' => $folder_key, 'name' => $folder_name, 'videos' => array());

        foreach($convention->archives as $row)
        {
            if ($row->isPlayable())
            {
                $folder_name = $folders[$row->folder];
                $videos[$folder_name]['videos'][] = $row;
            }
            else
            {
                $sources[] = $row;
            }
        }

        foreach($videos as $folder_name => $row)
        {
            if (empty($row['videos']))
                unset($videos[$folder_name]);
        }

        $this->view->videos = $videos;
        $this->view->sources = $sources;

        // Pull conventions.
        $conventions = Convention::getAllConventions();
        $this->view->conventions_archived = $conventions['archived'];
    }

    public function signupAction()
    {

    }
}