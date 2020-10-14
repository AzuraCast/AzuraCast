<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NowPlayingListeners")
 */
class NowPlayingListeners
{
    /**
     * Current listeners, either unique (if supplied) or total (non-unique)
     * @OA\Property(example=15)
     * @var int
     */
    public int $current = 0;

    /**
     * Total unique current listeners
     * @OA\Property(example=15)
     * @var int
     */
    public int $unique = 0;

    /**
     * Total non-unique current listeners
     * @OA\Property(example=20)
     * @var int
     */
    public int $total = 0;

    public function __construct(?array $listeners = [])
    {
        if (isset($listeners['current'])) {
            $this->current = (int)$listeners['current'];
        } else {
            $this->current = (int)($listeners['unique'] ?? $listeners['total'] ?? 0);
        }

        $this->unique = (int)($listeners['unique'] ?? $listeners['current'] ?? 0);
        $this->total = (int)($listeners['total'] ?? $listeners['current'] ?? 0);
    }
}
