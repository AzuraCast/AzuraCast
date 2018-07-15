<?php
namespace Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Backend\Liquidsoap;
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
        /** @var EntityManager $em */
        $em = $this->di[EntityManager::class];

        $station_repo = $em->getRepository(Entity\Station::class);

        /** @var Entity\Station $station */
        $station = $station_repo->find(1);

        /** @var Adapters $adapters */
        $adapters = $this->di[Adapters::class];

        /** @var Liquidsoap $ls */
        $ls = $adapters->getBackendAdapter($station);

        print_r($ls->command('help'));
        exit;

        return $response;
    }
}
