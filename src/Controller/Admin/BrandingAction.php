<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class BrandingAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        return $request->getView()->renderToResponse(
            $response,
            'admin/branding/index'
        );
    }
}
