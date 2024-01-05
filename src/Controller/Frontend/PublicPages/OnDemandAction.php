<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class OnDemandAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $embed */
        $embed = $params['embed'] ?? null;

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        // Get list of custom fields.
        /** @var array<array{id: int, short_name: string, name: string}> $customFieldsRaw */
        $customFieldsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT cf.id, cf.short_name, cf.name
                FROM App\Entity\CustomField cf ORDER BY cf.name ASC
            DQL
        )->getArrayResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[] = [
                'display_key' => 'custom_field_' . $row['id'],
                'key' => $row['short_name'],
                'label' => $row['name'],
            ];
        }

        $router = $request->getRouter();

        $pageClass = 'ondemand station-' . $station->getShortName();
        if (null !== $embed) {
            $pageClass .= ' embed';
        }

        $view = $request->getView();

        // Add station public code.
        $view->fetch(
            'frontend/public/partials/station-custom',
            ['station' => $station]
        );

        return $view->renderVuePage(
            response: $response->withHeader('X-Frame-Options', '*'),
            component: 'Public/OnDemand',
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
