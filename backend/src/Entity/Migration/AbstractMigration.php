<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\Migrations\AbstractMigration as DoctrineAbstractMigration;

abstract class AbstractMigration extends DoctrineAbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }
}
