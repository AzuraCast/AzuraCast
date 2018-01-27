<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class NowPlayingListeners
{
    /**
     * @param array $listeners
     */
    public function __construct($listeners = [])
    {
        $this->current = (int)$listeners['current'];
        $this->unique = (int)$listeners['unique'];
        $this->total = (int)$listeners['total'];
    }

    /**
     * Current listeners, either unique (if supplied) or total (non-unique)
     * @SWG\Property(example=15)
     * @var int
     */
    public $current;

    /**
     * Total unique current listeners
     * @SWG\Property(example=15)
     * @var int
     */
    public $unique;

    /**
     * Total non-unique current listeners
     * @SWG\Property(example=20)
     * @var int
     */
    public $total;
}