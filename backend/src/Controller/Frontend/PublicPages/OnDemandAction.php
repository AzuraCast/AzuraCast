<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Frontend\PublicPages\Traits\IsEmbeddable;
use App\Controller\SingleActionInterface;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class OnDemandAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use IsEmbeddable;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->enable_public_page) {
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

        $pageClass = 'ondemand station-' . $station->short_name;
        if ($this->isEmbedded($request, $params)) {
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
            title: __('On-Demand Media') . ' - ' . $station->name,
            layoutParams: [
                'page_class' => $pageClass,
                'hide_footer' => true,
            ],
            props: [
                'listUrl' => $router->fromHere('api:stations:ondemand:list'),
                'showDownloadButton' => $station->enable_on_demand_download,
                'customFields' => $customFields,
                'stationName' => $station->name,
            ]
        );
    }
}
