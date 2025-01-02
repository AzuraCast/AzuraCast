<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Rsas;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    public function __construct(
        private readonly Rsas $rsas,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            [
                'success' => true,
                'version' => $this->rsas->getVersion(),
                'hasLicense' => $this->rsas->hasLicense(),
            ]
        );
    }
}
