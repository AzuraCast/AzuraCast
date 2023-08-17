<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\SettingsAwareTrait;
use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Status;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<Settings> */
#[
    OA\Get(
        path: '/admin/settings',
        operationId: 'getSettings',
        description: 'List the current values of all editable system settings.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Settings')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/admin/settings',
        operationId: 'editSettings',
        description: 'Update settings to modify any settings provided.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Settings')
        ),
        tags: ['Administration: Settings'],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class SettingsController extends AbstractApiCrudController
{
    use SettingsAwareTrait;

    protected string $entityClass = Settings::class;

    public function __construct(
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $group */
        $group = $params['group'] ?? null;

        $context = [];
        if (null !== $group && in_array($group, Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->readSettings();
        return $response->withJson($this->toArray($settings, $context));
    }

    public function updateAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $group */
        $group = $params['group'] ?? null;

        $context = [];
        if (null !== $group && in_array($group, Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->settingsRepo->readSettings();

        if ($group === Settings::GROUP_GENERAL && !$settings->isSetupComplete()) {
            $settings->updateSetupComplete();
        }

        $this->editRecord((array)$request->getParsedBody(), $settings, $context);

        return $response->withJson(Status::success());
    }
}
