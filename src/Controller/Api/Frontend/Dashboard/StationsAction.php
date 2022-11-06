<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Entity;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class StationsAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();
        $acl = $request->getAcl();

        /** @var Entity\Station[] $stations */
        $stations = array_filter(
            $this->em->getRepository(Entity\Station::class)->findAll(),
            static function ($station) use ($acl) {
                /** @var Entity\Station $station */
                return $station->getIsEnabled() &&
                    $acl->isAllowed(StationPermissions::View, $station->getId());
            }
        );

        $listenersEnabled = $this->settingsRepo->readSettings()->isAnalyticsEnabled();

        $viewStations = [];
        foreach ($stations as $station) {
            $np = $this->npApiGenerator->currentOrEmpty($station);
            $np->resolveUrls($request->getRouter()->getBaseUrl());

            $row = new Entity\Api\Dashboard();
            $row->fromParentObject($np);

            $row->links = [
                'public' => $router->named('public:index', ['station_id' => $station->getShortName()]),
                'manage' => $router->named('stations:index:index', ['station_id' => $station->getId()]),
            ];

            if ($listenersEnabled && $acl->isAllowed(StationPermissions::Reports, $station->getId())) {
                $row->links['listeners'] = $router->named(
                    'stations:reports:listeners',
                    ['station_id' => $station->getId()]
                );
            }

            $viewStations[] = $row;
        }

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $viewStations = array_filter(
                $viewStations,
                static function (Entity\Api\Dashboard $row) use ($searchPhrase) {
                    return false !== mb_stripos($row->station->name, $searchPhrase);
                }
            );
        }

        $sort = $request->getParam('sort');
        usort(
            $viewStations,
            static function (Entity\Api\Dashboard $a, Entity\Api\Dashboard $b) use ($sort) {
                if ('listeners' === $sort) {
                    return $a->listeners->current <=> $b->listeners->current;
                }

                return $a->station->name <=> $b->station->name;
            }
        );

        if ('desc' === strtolower($request->getParam('sortOrder', 'asc'))) {
            $viewStations = array_reverse($viewStations);
        }

        return Paginator::fromArray($viewStations, $request)->write($response);
    }
}
