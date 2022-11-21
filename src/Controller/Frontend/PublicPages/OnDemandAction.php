<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class OnDemandAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $embed = null
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        if (!$station->getEnableOnDemand()) {
            throw new StationUnsupportedException();
        }

        // Get list of custom fields.
        $customFieldsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT cf.id, cf.short_name, cf.name
                FROM App\Entity\CustomField cf ORDER BY cf.name ASC
            DQL
        )->getArrayResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[] = [
                'display_key' => 'media_custom_fields_' . $row['short_name'],
                'key' => $row['short_name'],
                'label' => $row['name'],
            ];
        }

        $router = $request->getRouter();

        $pageClass = 'ondemand station-' . $station->getShortName();
        if (null !== $embed) {
            $pageClass .= ' embed';
        }

        return $request->getView()->renderVuePage(
            response: $response->withHeader('X-Frame-Options', '*'),
            component: 'Vue_PublicOnDemand',
            id: 'station-on-demand',
            layout: 'minimal',
            title: __('On-Demand Media') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => $pageClass,
                'hide_footer' => true,
            ],
            props: [
                'listUrl' => $router->fromHere('api:stations:ondemand:list'),
                'showDownloadButton' => $station->getEnableOnDemandDownload(),
                'customFields' => $customFields,
                'stationName' => $station->getName(),
            ]
        );
    }
}
