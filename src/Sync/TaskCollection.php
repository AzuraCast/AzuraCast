<?php
namespace App\Sync;

use App\Sync\Task\AbstractTask;
use Doctrine\Common\Collections\ArrayCollection;

class TaskCollection extends ArrayCollection
{
    public const SYNC_NOWPLAYING = 'nowplaying';

    public const SYNC_SHORT = 'short';

    public const SYNC_MEDIUM = 'medium';

    public const SYNC_LONG = 'long';

    /**
     * @param string $type
     *
     * @return AbstractTask[]
     */
    public function getTasks(string $type): array
    {
        return $this->get($type);
    }
}