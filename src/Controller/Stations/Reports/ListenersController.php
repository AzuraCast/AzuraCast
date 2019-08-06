<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListenersController
{
    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $view = \App\Http\RequestHelper::getView($request);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting(Entity\Settings::LISTENER_ANALYTICS, Entity\Analytics::LEVEL_ALL);

        if ($analytics_level !== Entity\Analytics::LEVEL_ALL) {
            return $view->renderToResponse($response, 'stations/reports/restricted');
        }

        return $view->renderToResponse($response, 'stations/reports/listeners');
    }
}
