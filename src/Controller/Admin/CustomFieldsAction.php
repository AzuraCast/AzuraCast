<?php

declare(strict_types=1);

namespace App\Controller\Admin;

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
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/CustomFields',
            id: 'admin-custom-fields',
            title: __('Custom Fields'),
            props: [
                'listUrl' => $router->fromHere('api:admin:custom_fields'),
                'autoAssignTypes' => MetadataTags::getNames(),
            ]
        );
    }
}
