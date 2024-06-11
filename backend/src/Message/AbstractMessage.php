<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueNames;

abstract class AbstractMessage
{
    public function getQueue(): QueueNames
    {
        return QueueNames::NormalPriority;
    }
}
