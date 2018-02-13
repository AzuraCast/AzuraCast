<?php
namespace Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
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

        $payload = file_get_contents('http://stations:8000/statistics?json=1');

        /** @var \AzuraCast\Sync\NowPlaying $sync_nowplaying */
        $sync_nowplaying = $this->di[\AzuraCast\Sync\NowPlaying::class];
        $sync_nowplaying->processStation($station, $payload);

        exit;
    }
}