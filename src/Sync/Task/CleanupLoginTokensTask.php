<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity;

final class CleanupLoginTokensTask extends AbstractTask
{
    public function __construct(
        private readonly Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
    ) {
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
