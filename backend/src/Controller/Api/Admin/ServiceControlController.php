<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Api\Admin\ServiceData;
use App\Entity\Api\Status;
use App\Entity\CustomField;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\ServiceControl;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/services',
        operationId: 'getServiceDetails',
        description: 'List the status of essential system services.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: ServiceData::class
                    )
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/services/restart/{service}',
        operationId: 'restartService',
        description: 'Restart the specified service.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: CustomField::class)
        ),
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
            new OA\Parameter(
                name: 'service',
                description: 'Service name.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class ServiceControlController
{
    public function __construct(
        private readonly ServiceControl $serviceControl
    ) {
    }

    public function getAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();
        $canRestart = $request->getAcl()->isAllowed(GlobalPermissions::All);

        $result = array_map(
            function (ServiceData $row) use ($router, $canRestart) {
                $row->links = [];

                if ($canRestart) {
                    $row->links['restart'] = $router->fromHere(
                        'api:admin:services:restart',
                        ['service' => $row->name]
                    );
                }

                return $row;
            },
            $this->serviceControl->getServices()
        );

        return $response->withJson($result);
    }

    public function restartAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $service */
        $service = $params['service'];

        $this->serviceControl->restart($service);

        return $response->withJson(Status::success());
    }
}
