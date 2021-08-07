<?php

declare(strict_types=1);

namespace App\Doctrine\Generator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;

class UuidV6Generator extends AbstractIdGenerator
{
    protected UuidFactoryInterface $factory;

    public function __construct()
    {
        $this->factory = clone Uuid::getFactory();
    }

    public function generate(EntityManager $em, $entity): string
    {
        $nodeProvider = new RandomNodeProvider();
        return $this->factory->uuid6($nodeProvider->getNode())->toString();
    }
}
