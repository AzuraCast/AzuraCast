<?php
namespace App\Doctrine;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Container\ContainerInterface;

/**
 * A dependency-injection aware repository locator.
 *
 * @package App\Doctrine
 */
class RepositoryFactory implements \Doctrine\ORM\Repository\RepositoryFactory
{
    /** @var ObjectRepository[] */
    protected $repository_list = [];

    /** @var ContainerInterface */
    protected $di;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $em, $entity_name)
    {
        $entity_meta = $em->getClassMetadata($entity_name);
        $repo_name = $entity_meta->getName();

        if (isset($this->repository_list[$repo_name])) {
            return $this->repository_list[$repo_name];
        }

        return $this->repository_list[$repo_name] = $this->createRepository($em, $entity_meta);
    }

    /**
     * Create a new repository instance for an entity class.
     *
     * @param EntityManagerInterface $entityManager
     * @param ClassMetadata $entity_meta
     * @return ObjectRepository
     */
    protected function createRepository(
        EntityManagerInterface $entityManager,
        ClassMetadata $entity_meta): ObjectRepository
    {
        $repo_class = $entity_meta->customRepositoryClassName
            ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        if ($this->di->has($repo_class)) {
            return $this->di->get($repo_class);
        }

        return new $repo_class($entityManager, $entity_meta);
    }
}
