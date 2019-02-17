<?php
namespace App\Form;

use App\Http\Request;
use Azura\Normalizer\DoctrineEntityNormalizer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A generic class that handles binding an entity to a
 */
class EntityForm extends \AzuraForms\Form
{
    /** @var EntityManager */
    protected $em;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var string The fully-qualified (::class) class name of the entity being managed. */
    protected $entityClass;

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
        ?array $defaults = null)
    {
        parent::__construct($options, $defaults);

        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
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
     * @param Request $request
     * @param object|null $record
     * @return object|bool The modified object if edited/created, or `false` if not processed.
     */
    public function process(Request $request, $record = null)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        // Populate the form with existing values (if they exist).
        if (null !== $record) {
            $this->populate($this->_normalizeRecord($record));
        }

        // Handle submission.
        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            $record = $this->_denormalizeToRecord($data, $record);

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

        return false;
    }

    /**
     * @param $record
     * @param array $context
     * @return array|bool|float|int|mixed|string
     */
    protected function _normalizeRecord($record, array $context = [])
    {
        return $this->serializer->normalize($record, null, array_merge($context, [
            DoctrineEntityNormalizer::NORMALIZE_TO_IDENTIFIERS => true,
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
     * @param $data
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
}
