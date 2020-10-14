<?php

namespace App\Event\Radio;

use App\Entity\Api\NowPlaying;
use Symfony\Contracts\EventDispatcher\Event;

class LoadNowPlaying extends Event
{
    /** @var NowPlaying[] */
    protected array $np = [];

    /**
     * @param array $np_raw
     * @param string|null $source
     */
    public function setNowPlaying(array $np_raw, $source = null): void
    {
        $np = array_filter($np_raw, function ($np_row) {
            return $np_row instanceof NowPlaying;
        });

        if (0 !== count($np)) {
            if ($source !== null) {
                foreach ($np as $np_row) {
                    /** @var NowPlaying $np_row */
                    $np_row->cache = $source;
                }
            }

            $this->np = $np;
            $this->stopPropagation();
        }
    }

    public function hasNowPlaying(): bool
    {
        return (0 !== count($this->np));
    }

    /**
     * @return NowPlaying[]
     */
    public function getNowPlaying(): array
    {
        return $this->np;
    }
}
