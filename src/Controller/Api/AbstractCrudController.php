<?php
namespace App\Controller\Api;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractCrudController
{
    /** @var EntityManager */
    protected $em;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var string The fully-qualified (::class) class name of the entity being managed. */
    protected $entityClass;

    /** @var string The route name used to generate the "self" links for each record. */
    protected $resourceRouteName;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManager $em, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @param $record
     * @return array
     */
    protected function _viewRecord($record): array
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        return $this->serializer->normalize($record, null, []);
    }

    /**
     * @param $data
     * @param null $record
     * @return object
     * @throws \App\Exception\Validation
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _createRecord($data, $record = null): object
    {
        if (null === $record) {
            $record = new $this->entityClass();
        }

        return $this->_editRecord($data, $record);
    }

    /**
     * @param $data
     * @param $record
     * @return object
     * @throws \App\Exception\Validation
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _editRecord($data, $record): object
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $this->serializer->denormalize($data, $this->entityClass, null, [
            'object_to_populate' => $record,
        ]);

        $errors = $this->validator->validate($record);

        if (count($errors) > 0) {
            throw new \App\Exception\Validation((string)$errors);
        }

        $this->em->persist($record);
        $this->em->flush($record);

        return $record;
    }

    /**
     * @param $record
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _deleteRecord($record): void
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $this->em->remove($record);
        $this->em->flush($record);
    }
}
