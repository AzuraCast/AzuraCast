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

    /**
     * @param array $listeners
     */
    public function __construct($listeners = [])
    {
        if (isset($listeners['current'])) {
            $this->current = (int)$listeners['current'];
        } else {
            $this->current = (int)($listeners['unique'] ?? $listeners['total']);
        }

        $this->unique = (int)($listeners['unique'] ?? $listeners['current']);
        $this->total = (int)($listeners['total'] ?? $listeners['current']);
    }
}
