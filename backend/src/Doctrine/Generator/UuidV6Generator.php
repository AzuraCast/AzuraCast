<?php

declare(strict_types=1);

namespace App\Doctrine\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Uuid;

final class UuidV6Generator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, object|null $entity): string
    {
        return (string)Uuid::v6();
    }
}
