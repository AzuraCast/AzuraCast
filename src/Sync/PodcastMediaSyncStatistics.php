<?php

declare(strict_types=1);

namespace App\Sync;

use App\Radio\Quota;
use Brick\Math\BigInteger;
use JsonSerializable;

class PodcastMediaSyncStatistics implements JsonSerializable
{
    public BigInteger $totalSize;
    public int $totalFiles = 0;
    public int $alreadyQueued = 0;
    public int $unchanged = 0;
    public int $updated = 0;
    public int $created = 0;
    public int $deleted = 0;

    public function __construct()
    {
        $this->totalSize = BigInteger::zero();
    }

    public function jsonSerialize(): array
    {
        return [
            'total_size' => $this->totalSize . ' (' . Quota::getReadableSize($this->totalSize) . ')',
            'total_files' => $this->totalFiles,
            'already_queued' => $this->alreadyQueued,
            'unchanged' => $this->unchanged,
            'updated' => $this->updated,
            'created' => $this->created,
            'deleted' => $this->deleted,
        ];
    }
}
