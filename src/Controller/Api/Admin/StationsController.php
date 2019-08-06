<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use Azura\Normalizer\DoctrineEntityNormalizer;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationsController extends AbstractAdminApiCrudController
{
    protected $entityClass = Entity\Station::class;
    protected $resourceRouteName = 'api:admin:station';

    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    public function __construct(EntityManager $em, Serializer $serializer, ValidatorInterface $validator)
    {
        parent::__construct($em, $serializer, $validator);

        $this->station_repo = $em->getRepository(Entity\Station::class);
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
    protected function _normalizeRecord($record, array $context = [])
    {
        return parent::_normalizeRecord($record, $context + [
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
    protected function _editRecord($data, $record = null, array $context = []): object
    {
        $create_mode = (null === $record);

        if (null === $data) {
            throw new \InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->_denormalizeToRecord($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new \App\Exception\Validation((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        if ($create_mode) {
            $this->station_repo->create($record);
        } else {
            $this->station_repo->edit($record);
        }

        $this->em->persist($record);
        $this->em->flush($record);
        return $record;
    }

    /** @inheritDoc */
    protected function _deleteRecord($record): void
    {
        $this->station_repo->destroy($record);
    }
}
