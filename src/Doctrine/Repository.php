<?php
namespace App\Doctrine;

use App\Normalizer\DoctrineEntityNormalizer;
use App\Settings;
use Closure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class Repository
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $entityClass;

    /** @var EntityRepository */
    protected $repository;

    /** @var Serializer */
    protected $serializer;

    /** @var Settings */
    protected $settings;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param Settings $settings
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, Serializer $serializer, Settings $settings, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->settings = $settings;
        $this->logger = $logger;

        if (!$this->entityClass) {
            $this->entityClass = $this->getEntityClass();
        }
        if (!$this->repository) {
            $this->repository = $em->getRepository($this->entityClass);
        }
    }

    /**
     * @return string The extrapolated likely entity name, based on this repository's class name.
     */
    protected function getEntityClass(): string
    {
        return str_replace(['Repository', '\\\\'], ['', '\\'], static::class);
    }

    /**
     * @return EntityRepository
     */
    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    /**
     * Generate an array result of all records.
     *
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     *
     * @return array
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC'): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($this->entityClass, 'e');

        if ($order_by) {
            $qb->orderBy('e.' . str_replace('e.', '', $order_by), $order_dir);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Generic dropdown builder function (can be overridden for specialized use cases).
     *
     * @param bool $add_blank
     * @param Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     *
     * @return array
     */
    public function fetchSelect($add_blank = false, Closure $display = null, $pk = 'id', $order_by = 'name'): array
    {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? 'Select...' : $add_blank;
        }

        // Build query for records.
        $qb = $this->em->createQueryBuilder()->from($this->entityClass, 'e');

        if ($display === null) {
            $qb->select('e.' . $pk)->addSelect('e.name')->orderBy('e.' . $order_by, 'ASC');
        } else {
            $qb->select('e')->orderBy('e.' . $order_by, 'ASC');
        }

        $results = $qb->getQuery()->getArrayResult();

        // Assemble select values and, if necessary, call $display callback.
        foreach ((array)$results as $result) {
            $key = $result[$pk];
            $value = ($display === null) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    /**
     * FromArray (A Doctrine 1 Classic)
     *
     * @param object $entity
     * @param array $source
     *
     * @return object
     */
    public function fromArray($entity, array $source)
    {
        return $this->serializer->denormalize($source, get_class($entity), null, [
            DoctrineEntityNormalizer::OBJECT_TO_POPULATE => $entity,
        ]);
    }

    /**
     * ToArray (A Doctrine 1 Classic)
     *
     * @param object $entity
     * @param bool $deep Iterate through collections associated with this item.
     * @param bool $form_mode Return values in a format suitable for ZendForm setDefault function.
     *
     * @return array
     */
    public function toArray($entity, $deep = false, $form_mode = false): array
    {
        return $this->serializer->normalize($entity, null, [
            DoctrineEntityNormalizer::NORMALIZE_TO_IDENTIFIERS => $form_mode,
        ]);
    }
}