<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\Enums\MetadataTags;
use Psr\Http\Message\ResponseInterface;

final class CustomFieldsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson([
            'autoAssignTypes' => MetadataTags::getNames(),
        ]);
    }
}
