<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Acl;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class StationsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator
    ): ResponseInterface {
        $router = $request->getRouter();
        $acl = $request->getAcl();

        /** @var Entity\Station[] $stations */
        $stations = array_filter(
            $em->getRepository(Entity\Station::class)->findAll(),
            static function ($station) use ($acl) {
                /** @var Entity\Station $station */
                return $station->isEnabled() &&
                    $acl->isAllowed(Acl::STATION_VIEW, $station->getId());
            }
        );

        $viewStations = [];
        foreach ($stations as $station) {
            $np = $npApiGenerator->currentOrEmpty($station);
            $np->resolveUrls($request->getRouter()->getBaseUrl());

            $row = new Entity\Api\Dashboard();
            $row->fromParentObject($np);

            $row->links = [
                'public' => (string)$router->named('public:index', ['station_id' => $station->getShortName()]),
                'manage' => (string)$router->named('stations:index:index', ['station_id' => $station->getId()]),
            ];

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

        $sortDesc = ('desc' === strtolower($request->getParam('sortOrder', 'asc')));
        if ($sortDesc) {
            $viewStations = array_reverse($viewStations);
        }

        return Paginator::fromArray($viewStations, $request)->write($response);
    }
}
