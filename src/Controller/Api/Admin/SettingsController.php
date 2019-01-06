<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Entity\Api\Admin\Settings */
    protected $api_settings;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(EntityManager $em, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;

        $this->settings_repo = $this->em->getRepository(Entity\Settings::class);

        $all_settings = $this->settings_repo->fetchAll();
        $this->api_settings = $this->serializer->denormalize($all_settings, Entity\Api\Admin\Settings::class);
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
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function listAction(Request $request, Response $response): ResponseInterface
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
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     * @throws \App\Exception\Validation
     */
    public function updateAction(Request $request, Response $response): ResponseInterface
    {
        $api_settings_obj = $this->serializer->denormalize($request->getParsedBody(), Entity\Api\Admin\Settings::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $this->api_settings,
        ]);

        $errors = $this->validator->validate($api_settings_obj);
        if (count($errors) > 0) {
            throw new \App\Exception\Validation((string)$errors);
        }

        $api_settings = $this->serializer->normalize($api_settings_obj);

        $this->settings_repo->setSettings($api_settings);

        return $response->withJson(new Entity\Api\Status());
    }
}
