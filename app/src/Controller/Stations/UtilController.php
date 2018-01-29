<?php
namespace Controller\Stations;
use App\Http\Request;
use App\Http\Response;

class UtilController extends BaseController
{
    /**
     * Restart all services associated with the radio.
     */
    public function restartAction(Request $request, Response $response): Response
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->getId());

        $this->station->writeConfiguration($this->di);

        $frontend = $this->station->getFrontendAdapter($this->di);
        $backend = $this->station->getBackendAdapter($this->di);

        $backend->stop();
        $frontend->stop();

        $frontend->start();
        $backend->start();

        $this->station->setHasStarted(true);
        $this->station->setNeedsRestart(false);

        $this->em->persist($this->station);
        $this->em->flush();
    }
}