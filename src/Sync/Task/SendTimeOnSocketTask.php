<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Service\Centrifugo;
use Psr\Log\LoggerInterface;

final class SendTimeOnSocketTask extends AbstractTask
{
    public function __construct(
        private readonly Centrifugo $centrifugo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return '* * * * *';
    }

    public function run(bool $force = false): void
    {
        if (!$this->centrifugo->isSupported()) {
            return;
        }

        $this->centrifugo->sendTime();
    }
}
