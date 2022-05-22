<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Shoutcast;
use Psr\Http\Message\ResponseInterface;

final class GetAction
{
    public function __construct(
        private readonly Shoutcast $shoutcast,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        return $response->withJson(
            [
                'success' => true,
                'version' => $this->shoutcast->getVersion(),
            ]
        );
    }
}
