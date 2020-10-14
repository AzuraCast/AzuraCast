<?php

namespace App\Controller\Api\Admin;

use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsController
{
    protected EntityManagerInterface $em;

    protected Serializer $serializer;

    protected ValidatorInterface $validator;

    protected Entity\Repository\SettingsRepository $settings_repo;

    protected Entity\Api\Admin\Settings $api_settings;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settings_repo,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;

        $this->settings_repo = $settings_repo;
        $all_settings = $settings_repo->fetchAll();

        /** @var Entity\Api\Admin\Settings $api_settings */
        $api_settings = $this->serializer->denormalize($all_settings, Entity\Api\Admin\Settings::class);
        $this->api_settings = $api_settings;
    }

    /**
     * @OA\Get(path="/admin/settings",
     *   tags={"Administration: Settings"},
     *   description="List the current values of all editable system settings.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Admin_Settings")
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
        return $response->withJson($this->api_settings);
    }

    /**
     * @OA\Put(path="/admin/settings",
     *   tags={"Administration: Settings"},
     *   description="Update settings to modify any settings provided.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_Admin_Settings")
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
        $api_settings_obj = $this->serializer->denormalize(
            $request->getParsedBody(),
            Entity\Api\Admin\Settings::class,
            null,
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $this->api_settings,
            ]
        );

        $errors = $this->validator->validate($api_settings_obj);
        if (count($errors) > 0) {
            throw new ValidationException((string)$errors);
        }

        $api_settings = $this->serializer->normalize($api_settings_obj);

        $this->settings_repo->setSettings($api_settings);

        return $response->withJson(new Entity\Api\Status());
    }
}
