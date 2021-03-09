<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

class CleanupLoginTokensTask extends AbstractTask
{
    protected Entity\Repository\UserLoginTokenRepository $loginTokenRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo
    ) {
        parent::__construct($em, $logger);

        $this->loginTokenRepo = $loginTokenRepo;
    }

    public function run(bool $force = false): void
    {
        $this->loginTokenRepo->cleanup();
    }
}
