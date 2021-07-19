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

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/embedrequests',
            [
                'station' => $station,
                'customFields' => $customFieldRepo->fetchArray(),
            ]
        );
    }
}
