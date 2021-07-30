<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractApiCrudController<Entity\Settings>
 */
class SettingsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\Settings::class;

    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @OA\Get(path="/admin/settings",
     *   tags={"Administration: Settings"},
     *   description="List the current values of all editable system settings.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Settings")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $settings = $this->settingsRepo->readSettings();
        return $response->withJson($this->toArray($settings));
    }

    /**
     * @OA\Put(path="/admin/settings",
     *   tags={"Administration: Settings"},
     *   description="Update settings to modify any settings provided.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Settings")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     *
     * @throws ValidationException
     */
    public function updateAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $settings = $this->settingsRepo->readSettings();
        $this->editRecord((array)$request->getParsedBody(), $settings);

        return $response->withJson(new Entity\Api\Status());
    }
}
