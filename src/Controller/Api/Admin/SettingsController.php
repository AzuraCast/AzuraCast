<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<Entity\Settings> */
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
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class SettingsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\Settings::class;

    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        ?string $group = null
    ): ResponseInterface {
        $context = [];
        if (null !== $group && in_array($group, Entity\Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->settingsRepo->readSettings();
        return $response->withJson($this->toArray($settings, $context));
    }

    public function updateAction(
        ServerRequest $request,
        Response $response,
        ?string $group = null
    ): ResponseInterface {
        $context = [];
        if (null !== $group && in_array($group, Entity\Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->settingsRepo->readSettings();

        if ($group === Entity\Settings::GROUP_GENERAL && !$settings->isSetupComplete()) {
            $settings->updateSetupComplete();
        }

        $this->editRecord((array)$request->getParsedBody(), $settings, $context);

        return $response->withJson(Entity\Api\Status::success());
    }
}
