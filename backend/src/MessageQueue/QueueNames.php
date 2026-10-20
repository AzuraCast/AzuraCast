<?php

declare(strict_types=1);

namespace App\MessageQueue;

enum QueueNames: string
{
    // "Fast" queues (that have their own dedicated processor)
    case FastHighPriority = 'high_priority';
    case FastNormalPriority = 'normal_priority';
    case FastLowPriority = 'low_priority';

    // "Slow" queues (tasks that take longer)
    case SlowMedia = 'media';
    case SlowBackup = 'backup';

    public function isFast(): bool
    {
        return match ($this) {
            self::FastHighPriority, self::FastNormalPriority, self::FastLowPriority => true,
            default => false
        };
    }
}
