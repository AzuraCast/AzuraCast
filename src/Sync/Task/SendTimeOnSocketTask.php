<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Service\Centrifugo;
use Psr\Log\LoggerInterface;

final class SendTimeOnSocketTask extends AbstractTask
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly Centrifugo $centrifugo,
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    public function run(bool $force = false): void
    {
        if (!$this->centrifugo->isSupported()) {
            return;
        }

        $this->centrifugo->sendTime();
    }
}
