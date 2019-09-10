<?php
namespace App\Controller\Api;

use App\Exception\ValidationException;
use Azura\Http\RouterInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiCrudController
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
     * @param RouterInterface $router
     * @return mixed
     */
    protected function _viewRecord($record, RouterInterface $router)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->_normalizeRecord($record);

        $return['links'] = [
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], true),
        ];
        return $return;
    }

    /**
     * Modern version of $record->toArray().
     *
     * @param object $record
     * @param array $context
     * @return array|mixed
     */
    protected function _normalizeRecord($record, array $context = [])
    {
        return $this->serializer->normalize($record, null, array_merge($context, [
            ObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ObjectNormalizer::MAX_DEPTH_HANDLER => function (
                $innerObject,
                $outerObject,
                string $attributeName,
                string $format = null,
                array $context = []
            ) {
                return $this->_displayShortenedObject($innerObject);
            },
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function (
                $object,
                string $format = null,
                array $context = []
            ) {
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
     * @param array $context
     * @return object
     */
    protected function _editRecord($data, $record = null, array $context = []): object
    {
        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->_denormalizeToRecord($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($record);
        $this->em->flush($record);

        return $record;
    }

    /**
     * Modern equivalent of $object->fromArray($data).
     *
     * @param array $data
     * @param object|null $record
     * @param array $context
     * @return object
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
    {
        if (null !== $record) {
            $context[ObjectNormalizer::OBJECT_TO_POPULATE] = $record;
        }

        return $this->serializer->denormalize($data, $this->entityClass, null, $context);
    }

    /**
     * @param object $record
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function _deleteRecord($record): void
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $this->em->remove($record);
        $this->em->flush($record);
    }
}
