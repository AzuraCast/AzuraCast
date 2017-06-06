<?php
namespace Controller\Stations;

class UtilController extends BaseController
{
    /**
     * Restart all services associated with the radio.
     */
    public function restartAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->id);

        $this->station->writeConfiguration($this->di);

        $frontend = $this->station->getFrontendAdapter($this->di);
        $backend = $this->station->getBackendAdapter($this->di);

        $backend->stop();
        $frontend->stop();

        $frontend->start();
        $backend->start();

        $this->station->has_started = true;
        $this->station->needs_restart = false;

        $this->em->persist($this->station);
        $this->em->flush();
    }
}