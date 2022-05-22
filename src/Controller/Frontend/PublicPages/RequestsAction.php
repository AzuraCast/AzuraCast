<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RequestsAction
{
    public function __construct(
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $router = $request->getRouter();
        $customization = $request->getCustomization();

        return $request->getView()->renderVuePage(
            response: $response
                ->withHeader('X-Frame-Options', '*'),
            component: 'Vue_PublicRequests',
            id: 'song-requests',
            layout: 'minimal',
            title: __('Requests') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'embed station-' . $station->getShortName(),
                'hide_footer' => true,
            ],
            props: [
                'customFields' => $this->customFieldRepo->fetchArray(),
                'showAlbumArt' => !$customization->hideAlbumArt(),
                'requestListUri' => (string)$router->named('api:requests:list', [
                    'station_id' => $station->getId(),
                ]),
            ],
        );
    }
}
