<?php

declare(strict_types=1);

namespace App\Doctrine\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;

final class UuidV6Generator extends AbstractIdGenerator
{
    private readonly UuidFactoryInterface $factory;

    public function __construct()
    {
        $this->factory = clone Uuid::getFactory();
    }

    public function generateId(EntityManagerInterface $em, $entity)
    {
        $nodeProvider = new RandomNodeProvider();
        return $this->factory->uuid6($nodeProvider->getNode())->toString();
    }
}
