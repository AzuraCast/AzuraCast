<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

abstract class AbstractMessage
{
    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_NORMAL_PRIORITY;
    }
}
