<?php
namespace App\Controller\Api;

use Azura\Http\Router;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationList;
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
     * @param object $record
     * @param Router $router
     * @return mixed
     */
    protected function _viewRecord($record, Router $router)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->_normalizeRecord($record);

        $return['links'] = [
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], true),
        ];
        return $return;
    }

    protected function _normalizeRecord($record, array $context = [])
    {
        return $this->serializer->normalize($record, null, array_merge($context, [
            ObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ObjectNormalizer::MAX_DEPTH_HANDLER => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = array()) {
                return $this->_displayShortenedObject($innerObject);
            },
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, string $format = null, array $context = array()) {
                return $this->_displayShortenedObject($object);
            },
        ]));
    }

    /**
     * @param object $object
     * @return mixed
     */
    protected function _displayShortenedObject($object)
    {
        if (method_exists($object, 'getName')) {
            return $object->getName();
        }

        return $object->getId();
    }

    /**
     * @param array $data
     * @param object|null $record
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
     * @param array $data
     * @param object $record
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

        $this->_denormalizeToRecord($data, $record);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new \App\Exception\Validation((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($record);
        $this->em->flush($record);

        return $record;
    }

    /**
     * @param array $data
     * @param object $record
     * @param array $context
     */
    protected function _denormalizeToRecord($data, $record, array $context = []): void
    {
        $this->serializer->denormalize($data, $this->entityClass, null, array_merge($context, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $record,
        ]));
    }

    /**
     * @param object $record
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
