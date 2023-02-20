<?php

declare(strict_types=1);

namespace App\MessageQueue;

enum QueueNames: string
{
    case HighPriority = 'high_priority';
    case NormalPriority = 'normal_priority';
    case LowPriority = 'low_priority';
    case Media = 'media';
    case PodcastMedia = 'podcast_media';
}
