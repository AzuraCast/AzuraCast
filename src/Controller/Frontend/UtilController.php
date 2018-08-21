<?php
namespace App\Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Webhook\Dispatcher;
use Doctrine\ORM\EntityManager;
use Slim\Container;
use App\Entity;

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
        print_r(array_keys($request->getAttributes()));

        print_r($request->getAttribute('routeInfo'));
        print_r(get_class($request->getAttribute('route')));

        exit;

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
