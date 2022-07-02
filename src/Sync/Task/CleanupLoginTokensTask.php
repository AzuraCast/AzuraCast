<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

final class CleanupLoginTokensTask extends AbstractTask
{
    public function __construct(
        private readonly Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return '12 * * * *';
    }

    public function run(bool $force = false): void
    {
        $this->loginTokenRepo->cleanup();
    }
}
