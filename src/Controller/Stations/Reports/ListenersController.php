<?php
namespace App\Controller\Stations\Reports;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

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
        $view = $request->getView();

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);

        if ($analytics_level !== Entity\Analytics::LEVEL_ALL) {
            return $view->renderToResponse($response, 'stations/reports/restricted');
        }

        return $view->renderToResponse($response, 'stations/reports/listeners');
    }
}
