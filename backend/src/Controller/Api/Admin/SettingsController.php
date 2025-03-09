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
        summary: 'List the current values of all editable system settings.',
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: Settings::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/admin/settings',
        operationId: 'editSettings',
        summary: 'Update settings to modify any settings provided.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: Settings::class)
        ),
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
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
