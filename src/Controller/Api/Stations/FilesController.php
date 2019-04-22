<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilesController extends AbstractStationApiCrudController
{
    protected $entityClass = Entity\StationMedia::class;
    protected $resourceRouteName = 'api:stations:media';

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Filesystem $filesystem
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Filesystem $filesystem
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->filesystem = $filesystem;
    }


}
