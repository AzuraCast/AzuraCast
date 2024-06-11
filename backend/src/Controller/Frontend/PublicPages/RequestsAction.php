<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RequestsAction implements SingleActionInterface
{
    public function __construct(
        private readonly CustomFieldRepository $customFieldRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        $router = $request->getRouter();
        $customization = $request->getCustomization();

        return $request->getView()->renderVuePage(
            response: $response
                ->withHeader('X-Frame-Options', '*'),
            component: 'Public/Requests',
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
                'requestListUri' => $router->named('api:requests:list', [
                    'station_id' => $station->getId(),
                ]),
            ],
        );
    }
}
