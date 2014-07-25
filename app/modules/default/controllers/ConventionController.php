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
        $convention = $this->_getConvention();

        if ($convention)
        {
            $this->view->convention = $convention;

            $videos = array();
            $folders = ConventionArchive::getFolders();

            foreach($convention->archives as $row)
            {
                if ($row->isPlayable())
                {
                    $folder_name = $folders[$row->folder];
                    $videos[$folder_name][] = $row;
                }
            }

            $this->view->videos = $videos;
            $this->render();
        }
        else
        {
            /* TODO: Directory of conventions. */
        }
    }

    public function signupAction()
    {

    }

}