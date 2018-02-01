<?php
namespace Controller\Stations;

use App\Mvc\View;
use AzuraCast\Radio\Backend\BackendAbstract;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;
use AzuraCast\Radio\Configuration;

class UtilController
{
    /** @var EntityManager */
    protected $em;

    /** @var Configuration */
    protected $configuration;

    /**
     * UtilController constructor.
     * @param EntityManager $em
     * @param Configuration $configuration
     */
    public function __construct(EntityManager $em, Configuration $configuration)
    {
        $this->em = $em;
        $this->configuration = $configuration;
    }

    /**
     * Restart all services associated with the radio.
     */
    public function restartAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        $this->configuration->writeConfiguration($station);

        $backend->stop();
        $frontend->stop();

        $frontend->start();
        $backend->start();

        $station->setHasStarted(true);
        $station->setNeedsRestart(false);

        $this->em->persist($station);
        $this->em->flush();

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/util/restart');
    }
}