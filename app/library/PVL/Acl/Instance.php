<?php
namespace PVL\Acl;

class Instance extends \App\Acl\Instance
{
    /**
     * Returns TRUE if the user can see the "Admin" tab at the top of the page.
     *
     * @return bool
     */
    public function canSeeAdminDashboard()
    {
        static $permission_granted;

        if ($permission_granted === NULL)
            $permission_granted = $this->_canSeeAdminDashboard();

        return $permission_granted;
    }

    /**
     * Internal function to generate a cached result for the "admin tab" ACL request.
     *
     * @internal
     * @return bool
     */
    protected function _canSeeAdminDashboard()
    {
        // Early deny for anonymous users.
        if (!$this->isAllowed('is logged in'))
            return false;

        if ($this->isAllowed('view administration'))
            return true;

        if ($this->canSeeStationCenter())
            return true;

        if ($this->canSeePodcastCenter())
            return true;

        return false;
    }

    /**
     * Returns TRUE if the user can see any radio station's Station Center.
     *
     * @return bool
     */
    public function canSeeStationCenter()
    {
        if ($this->isAllowed('manage stations'))
            return true;

        $auth = $this->di->get('auth');
        if (!$auth->isLoggedIn())
            return false;

        $user = $auth->getLoggedInUser();

        if ($user->stations->count() > 0)
        {
            foreach($user->stations as $station)
            {
                if ($station->is_active)
                    return true;
            }
        }

        return false;
    }

    /**
     * Returns TRUE if the user can see any podcast's Podcast Center.
     *
     * @return bool
     */
    public function canSeePodcastCenter()
    {
        if ($this->isAllowed('manage podcasts'))
            return true;

        $auth = $this->di->get('auth');
        if (!$auth->isLoggedIn())
            return false;

        $user = $auth->getLoggedInUser();

        if ($user->podcasts->count() > 0)
        {
            foreach($user->podcasts as $podcast)
            {
                if ($podcast->is_approved)
                    return true;
            }
        }

        return false;
    }

}