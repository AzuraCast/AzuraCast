<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\SHOUTcast;
use Psr\Http\Message\ResponseInterface;

class GetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SHOUTcast $shoutcast
    ): ResponseInterface {
        return $response->withJson(
            [
                'success' => true,
                'version' => $shoutcast->getVersion(),
            ]
        );
    }
}
