<?php

declare(strict_types=1);

namespace App\Container;

use App\Doctrine\ReloadableEntityManagerInterface;
use DI\Attribute\Inject;

trait EntityManagerAwareTrait
{
    protected ReloadableEntityManagerInterface $em;

    #[Inject]
    public function setEntityManager(ReloadableEntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    public function getEntityManager(): ReloadableEntityManagerInterface
    {
        return $this->em;
    }
}
