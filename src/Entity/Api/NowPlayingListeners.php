<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NowPlayingListeners")
 */
class NowPlayingListeners
{
    /**
     * @param array $listeners
     */
    public function __construct($listeners = [])
    {
        $this->current = (int)$listeners['current'];
        $this->unique = (int)($listeners['unique'] ?? $listeners['current']);
        $this->total = (int)($listeners['total'] ?? $listeners['current']);
    }

    /**
     * Current listeners, either unique (if supplied) or total (non-unique)
     * @OA\Property(example=15)
     * @var int
     */
    public $current;

    /**
     * Total unique current listeners
     * @OA\Property(example=15)
     * @var int
     */
    public $unique;

    /**
     * Total non-unique current listeners
     * @OA\Property(example=20)
     * @var int
     */
    public $total;
}
