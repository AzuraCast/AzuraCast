<?php

declare(strict_types=1);

namespace App\Sync;

class QueuedPodcastMediaMessages
{
    protected array $queuedUpdatePodcastMedia;
    protected array $queuedNewPodcastMediaFiles;

    public function __construct()
    {
        $this->queuedUpdatePodcastMedia = [];
        $this->queuedNewPodcastMediaFiles = [];
    }

    public function isPodcastMediaUpdateQueued(int $podcastMediaId): bool
    {
        return isset($this->queuedUpdatePodcastMedia[$podcastMediaId]);
    }

    public function addQueuedUpdatePodcastMedia(int $podcastMediaId): void
    {
        $this->queuedUpdatePodcastMedia[$podcastMediaId] = true;
    }

    public function isNewPodcastMediaFileQueued(string $path): bool
    {
        return isset($this->queuedNewPodcastMediaFiles[$path]);
    }

    public function addQueuedNewPodcastMediaFile(string $path): void
    {
        $this->queuedNewPodcastMediaFiles[$path] = true;
    }
}
