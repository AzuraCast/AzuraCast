<?php
namespace Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
use AzuraCast\Webhook\Dispatcher;
use Doctrine\ORM\EntityManager;
use Slim\Container;
use Entity;

class UtilController
{
    /** @var Container */
    protected $di;

    /**
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function testAction(Request $request, Response $response): Response
    {
        \App\Debug::setEchoMode(true);

        /** @var EntityManager $em */
        $em = $this->di[EntityManager::class];

        $station_repo = $em->getRepository(Entity\Station::class);

        /** @var Entity\Station $station */
        $station = $station_repo->find(1);

        $np = $station->getNowplaying();

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->di[Dispatcher::class];

        $dispatcher->dispatch($station, new Entity\Api\NowPlaying(), $np);

        exit;
    }
}