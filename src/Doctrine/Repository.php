<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Environment;
use App\Normalizer\DoctrineEntityNormalizer;
use Closure;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @template TEntity as object
 */
class Repository
{
    /** @var class-string<TEntity> */
    protected string $entityClass;

    /** @var ObjectRepository<TEntity> */
    protected ObjectRepository $repository;

    public function __construct(
        protected ReloadableEntityManagerInterface $em,
        protected Serializer $serializer,
        protected Environment $environment,
        protected LoggerInterface $logger
    ) {
        if (!isset($this->entityClass)) {
            /** @var class-string<TEntity> $defaultClass */
            $defaultClass = str_replace(['Repository', '\\\\'], ['', '\\'], static::class);
            $this->entityClass = $defaultClass;
        }

        if (!isset($this->repository)) {
            $this->repository = $em->getRepository($this->entityClass);
        }
    }

    public function getRepository(): ObjectRepository
    {
        return $this->repository;
    }

    /**
     * Generate an array result of all records.
     *
     * @param bool $cached
     * @param string|null $order_by
     * @param string $order_dir
     *
     * @return mixed[]
     */
    public function fetchArray(bool $cached = true, ?string $order_by = null, string $order_dir = 'ASC'): array
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
     * @param bool|string $add_blank
     * @param Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     *
     * @return mixed[]
     */
    public function fetchSelect(
        bool|string $add_blank = false,
        Closure $display = null,
        string $pk = 'id',
        string $order_by = 'name'
    ): array {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? __('Select...') : $add_blank;
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
            $select[$key] = ($display === null) ? $result['name'] : $display($result);
        }

        return $select;
    }

    /**
     * FromArray (A Doctrine 1 Classic)
     *
     * @param object $entity
     * @param array $source
     */
    public function fromArray(object $entity, array $source): object
    {
        return $this->serializer->denormalize(
            $source,
            get_class($entity),
            null,
            [
                DoctrineEntityNormalizer::OBJECT_TO_POPULATE => $entity,
            ]
        );
    }

    /**
     * ToArray (A Doctrine 1 Classic)
     *
     * @param object $entity
     * @param bool $deep Iterate through collections associated with this item.
     * @param bool $form_mode Return values in a format suitable for ZendForm setDefault function.
     *
     * @return mixed[]
     */
    public function toArray(object $entity, bool $deep = false, bool $form_mode = false): array
    {
        return (array)$this->serializer->normalize(
            $entity,
            null,
            [
                DoctrineEntityNormalizer::NORMALIZE_TO_IDENTIFIERS => $form_mode,
            ]
        );
    }
}
