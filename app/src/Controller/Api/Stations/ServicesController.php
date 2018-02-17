<?php
namespace Controller\Api\Stations;

use App\Http\Request;
use App\Http\Response;
use Azuracast\Radio;
use AzuraCast\Radio\Configuration;
use Doctrine\ORM\EntityManager;
use Entity;

class ServicesController
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

        /** @var Radio\Backend\BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        /** @var Radio\Frontend\FrontendAbstract $frontend */
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

        return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s restarted.'), _('Station'))));
    }

    public function frontendAction(Request $request, Response $response, $station_id, $do = 'restart'): Response
    {
        /** @var Radio\Frontend\FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        switch ($do) {
            case "stop":
                $frontend->stop();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s stopped.'), _('Frontend'))));
            break;

            case "start":
                $frontend->start();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s started.'), _('Frontend'))));
            break;

            case "restart":
            default:
                $frontend->stop();
                $frontend->write();
                $frontend->start();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s restarted.'), _('Frontend'))));
            break;
        }
    }

    public function backendAction(Request $request, Response $response, $station_id, $do = 'restart'): Response
    {
        /** @var Radio\Backend\BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        switch ($do) {
            case "skip":
                if (method_exists($backend, 'skip')) {
                    $backend->skip();
                }

                return $response->withJson(new Entity\Api\Status(true, _('Song skipped.')));
            break;

            case "stop":
                $backend->stop();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s stopped.'), _('Backend'))));
                break;

            case "start":
                $backend->start();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s started.'), _('Backend'))));
                break;

            case "restart":
            default:
                $backend->stop();
                $backend->write();
                $backend->start();

                return $response->withJson(new Entity\Api\Status(true, sprintf(_('%s restarted.'), _('Backend'))));
                break;
        }
    }

}