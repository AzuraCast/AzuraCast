<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Station;
use App\Environment;
use App\Exception;
use App\Http\ServerRequest;
use App\Normalizer\DoctrineEntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A generic class that handles binding an entity to an AzuraForms
 * instance and moving the data back and forth.
 *
 * This class exists primarily to facilitate the switch to Symfony's
 * Serializer and Validator classes, to allow for API parity.
 *
 * @template TEntity of object
 */
class EntityForm extends Form
{
    /** @var class-string<TEntity> The fully-qualified (::class) class name of the entity being managed. */
    protected string $entityClass;

    /** @var array The default context sent to form normalization/denormalization functions. */
    protected array $defaultContext = [];

    protected ?Station $station = null;

    public function __construct(
        protected EntityManagerInterface $em,
        protected Serializer $serializer,
        protected ValidatorInterface $validator,
        array $options = [],
        ?array $defaults = null
    ) {
        parent::__construct($options, $defaults);
    }

    /**
     * @return class-string<TEntity>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return ObjectRepository<TEntity>
     */
    public function getEntityRepository(): ObjectRepository
    {
        if (!isset($this->entityClass)) {
            throw new Exception('Entity class name is not specified.');
        }

        return $this->em->getRepository($this->entityClass);
    }

    /**
     * @param ServerRequest $request
     * @param TEntity|null $record
     *
     * @return TEntity|bool The modified object if edited/created, or `false` if not processed.
     */
    public function process(ServerRequest $request, ?object $record = null): object|bool
    {
        if (!isset($this->entityClass)) {
            throw new Exception('Entity class name is not specified.');
        }

        if (null !== $record && !($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        // Populate the form with existing values (if they exist).
        if (null !== $record) {
            $this->populate($this->normalizeRecord($record));
        }

        // Handle submission.
        if ($this->isValid($request)) {
            $data = $this->getValues();

            $record = $this->denormalizeToRecord($data, $record);

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if (isset($this->fields[$field_name])) {
                        $this->fields[$field_name]->addError((string)$error->getMessage());
                    } else {
                        $this->addError((string)$error->getMessage());
                    }
                }
                return false;
            }

            $this->em->persist($record);
            $this->em->flush();

            // Intentionally refresh the station entity in case it didn't refresh elsewhere.
            if ($this->station instanceof Station && Environment::getInstance()->isTesting()) {
                $this->em->refresh($this->station);
            }

            return $record;
        }

        return false;
    }

    /**
     * The old ->toArray().
     *
     * @param TEntity $record
     * @param array $context
     *
     * @return mixed[]
     */
    protected function normalizeRecord(object $record, array $context = []): array
    {
        $context = array_merge(
            $this->defaultContext,
            $context,
            [
                DoctrineEntityNormalizer::NORMALIZE_TO_IDENTIFIERS => true,
                ObjectNormalizer::ENABLE_MAX_DEPTH => true,
                ObjectNormalizer::MAX_DEPTH_HANDLER => function (
                    $innerObject,
                    $outerObject,
                    string $attributeName,
                    string $format = null,
                    array $context = []
                ) {
                    return $this->displayShortenedObject($innerObject);
                },
                ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function (
                    $object,
                    string $format = null,
                    array $context = []
                ) {
                    return $this->displayShortenedObject($object);
                },
            ]
        );

        return (array)$this->serializer->normalize($record, null, $context);
    }

    /**
     * @param object $object
     *
     */
    protected function displayShortenedObject(object $object): mixed
    {
        if (method_exists($object, 'getName')) {
            return $object->getName();
        }

        if ($object instanceof IdentifiableEntityInterface) {
            return $object->getIdRequired();
        }

        if ($object instanceof \Stringable) {
            return (string)$object;
        }

        return get_class($object) . ': ' . spl_object_hash($object);
    }

    /**
     * The old ->fromArray().
     *
     * @param array $data
     * @param TEntity|null $record
     * @param array $context
     *
     * @return TEntity
     */
    protected function denormalizeToRecord(array $data, ?object $record = null, array $context = []): object
    {
        $context = array_merge($this->defaultContext, $context);

        if (null !== $record) {
            $context[ObjectNormalizer::OBJECT_TO_POPULATE] = $record;
        }

        return $this->serializer->denormalize($data, $this->entityClass, null, $context);
    }

    /**
     * Modify the default context sent to all normalization/denormalization functions.
     *
     * @param int|string $key
     * @param null $value
     */
    public function setDefaultContext(int|string $key, $value = null): void
    {
        $this->defaultContext[$key] = $value;
    }

    /**
     * Shortcut function used to specify the station when initializing new classes.
     *
     * @param Station $station
     */
    public function setStation(Station $station): void
    {
        $this->station = $station;

        $this->defaultContext[ObjectNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS] = [
            $this->entityClass => [
                'station' => $station,
            ],
        ];
    }
}
