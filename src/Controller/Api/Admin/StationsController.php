<?php

namespace App\Controller\Api\Admin;

use App\Entity;
use App\Exception\ValidationException;
use App\Normalizer\DoctrineEntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationsController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\Station::class;
    protected string $resourceRouteName = 'api:admin:station';

    protected Entity\Repository\StationRepository $station_repo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationRepository $station_repo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->station_repo = $station_repo;
    }

    /**
     * @OA\Get(path="/admin/stations",
     *   tags={"Administration: Stations"},
     *   description="List all current stations in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Station"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/admin/stations",
     *   tags={"Administration: Stations"},
     *   description="Create a new station.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Retrieve details for a single station.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Update details of a single station.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Delete a single station.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    /** @inheritDoc */
    protected function toArray($record, array $context = []): array
    {
        return parent::toArray($record, $context + [
                DoctrineEntityNormalizer::IGNORED_ATTRIBUTES => [
                    'adapter_api_key',
                    'nowplaying',
                    'nowplaying_timestamp',
                    'automation_timestamp',
                    'needs_restart',
                    'has_started',
                ],
            ]);
    }

    /** @inheritDoc */
    protected function editRecord($data, $record = null, array $context = []): object
    {
        $create_mode = (null === $record);

        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->fromArray($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($record);
        $this->em->flush();

        if ($create_mode) {
            return $this->station_repo->create($record);
        }

        return $this->station_repo->edit($record);
    }

    /** @inheritDoc */
    protected function deleteRecord($record): void
    {
        $this->station_repo->destroy($record);
    }
}
