<?php
namespace App\Form;

use App\Entity\Station;
use App\Http\ServerRequest;
use App\Settings;
use Azura\Doctrine\Repository;
use Azura\Exception;
use Azura\Normalizer\DoctrineEntityNormalizer;
use Doctrine\ORM\EntityManager;
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
 */
class EntityForm extends Form
{
    /** @var EntityManager */
    protected $em;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var string The fully-qualified (::class) class name of the entity being managed. */
    protected $entityClass;

    /** @var array The default context sent to form normalization/denormalization functions. */
    protected $defaultContext = [];

    /** @var Station|null */
    protected $station;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param array $options
     * @param array|null $defaults
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $options = [],
        ?array $defaults = null
    ) {
        parent::__construct($options, $defaults);

        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * @return Repository
     */
    public function getEntityRepository(): Repository
    {
        if (null === $this->entityClass) {
            throw new Exception('Entity class name is not specified.');
        }

        return $this->em->getRepository($this->entityClass);
    }

    /**
     * @param ServerRequest $request
     * @param object|null $record
     * @return object|bool The modified object if edited/created, or `false` if not processed.
     */
    public function process(ServerRequest $request, $record = null)
    {
        if (null === $this->entityClass) {
            throw new Exception('Entity class name is not specified.');
        }

        if (null !== $record && !($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        // Populate the form with existing values (if they exist).
        if (null !== $record) {
            $this->populate($this->_normalizeRecord($record));
        }

        // Handle submission.
        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            $record = $this->_denormalizeToRecord($data, $record);

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if (isset($this->fields[$field_name])) {
                        $this->fields[$field_name]->addError($error->getMessage());
                    } else {
                        $this->addError($error->getMessage());
                    }
                }
                return false;
            }

            $this->em->persist($record);
            $this->em->flush($record);

            // Intentionally refresh the station entity in case it didn't refresh elsewhere.
            if ($this->station instanceof Station && Settings::getInstance()->isTesting()) {
                $this->em->refresh($this->station);
            }

            return $record;
        }

        return false;
    }

    /**
     * The old ->toArray().
     *
     * @param object $record
     * @param array $context
     * @return array
     */
    protected function _normalizeRecord($record, array $context = []): array
    {
        $context = array_merge($this->defaultContext, $context, [
            DoctrineEntityNormalizer::NORMALIZE_TO_IDENTIFIERS => true,
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
        ]);

        return $this->serializer->normalize($record, null, $context);
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
     * The old ->fromArray().
     *
     * @param array $data
     * @param object|null $record
     * @param array $context
     * @return object
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
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
     * @param string|int $key
     * @param null $value
     */
    public function setDefaultContext($key, $value = null): void
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
