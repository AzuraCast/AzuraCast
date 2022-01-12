<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class RequestsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\CustomFieldRepository $customFieldRepo
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $router = $request->getRouter();
        $customization = $request->getCustomization();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_PublicRequests',
            id: 'song-requests',
            layout: 'minimal',
            title: __('Requests') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'embed station-' . $station->getShortName(),
                'hide_footer' => true,
            ],
            props: [
                'customFields' => $customFieldRepo->fetchArray(),
                'showAlbumArt' => !$customization->hideAlbumArt(),
                'requestListUri' => (string)$router->named('api:requests:list', [
                    'station_id' => $station->getId(),
                ]),
            ],
        );
    }
}
