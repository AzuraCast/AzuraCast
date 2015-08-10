<?php
namespace Modules\Podcasts\Controllers;

use Entity\Podcast;

class BaseController extends \DF\Phalcon\Controller
{
    /*
     * @var array All available podcasts.
     */
    protected $podcasts;

    /**
     * @var Podcast The current active podcast.
     */
    protected $podcast;

    protected function preDispatch()
    {
        parent::preDispatch();

        $this->forceSecure();

        $user = $this->auth->getLoggedInUser();

        // Compile list of visible stations.
        $all_podcasts = Podcast::fetchAll();

        $podcasts = array();
        foreach($all_podcasts as $podcast)
        {
            if ($podcast->canManage($user))
                $podcasts[$podcast->id] = $podcast;
        }

        $this->podcasts = $podcasts;
        $this->view->podcasts = $podcasts;

        // Assign a station if one is selected.
        if ($this->hasParam('podcast'))
        {
            $podcast_id = (int)$this->getParam('podcast');
            if (isset($podcasts[$podcast_id]))
            {
                $this->podcast = $podcasts[$podcast_id];
                $this->view->podcast = $this->podcast;

                $this->view->hide_title = true;
            }
            else
            {
                throw new \DF\Exception\PermissionDenied;
            }
        }
        else if (count($this->podcasts) == 1)
        {
            // Convenience auto-redirect for single-station admins.
            $this->redirectFromHere(array('podcast' => key($this->podcasts)));
            return false;
        }

        // Force a redirect to the "Select" page if no station ID is specified.
        if (!$this->podcast)
            throw new \DF\Exception\PermissionDenied;
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }
}