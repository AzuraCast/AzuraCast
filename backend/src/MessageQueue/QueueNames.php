<?php

declare(strict_types=1);

namespace App\MessageQueue;

enum QueueNames: string
{
    // "Fast" queues (that have their own dedicated processor)
    case HighPriority = 'high_priority';
    case NormalPriority = 'normal_priority';
    case LowPriority = 'low_priority';

    // "Slow" queues (tasks that take longer)
    case Media = 'media';
    case Backup = 'backup';

    public function isFast(): bool
    {
        return match ($this) {
            self::HighPriority, self::NormalPriority, self::LowPriority => true,
            default => false
        };
    }
}
