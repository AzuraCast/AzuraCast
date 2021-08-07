<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Api\NowPlaying;
use App\Entity\Station;
use Symfony\Contracts\EventDispatcher\Event;

class LoadNowPlaying extends Event
{
    /** @var NowPlaying[] */
    protected array $np = [];

    /**
     * @param array $np_raw
     * @param string|null $source
     */
    public function setNowPlaying(array $np_raw, ?string $source = null): void
    {
        $np = array_filter(
            $np_raw,
            static function ($np_row) {
                return $np_row instanceof NowPlaying;
            }
        );

        if (0 !== count($np)) {
            if ($source !== null) {
                foreach ($np as $np_row) {
                    /** @var NowPlaying $np_row */
                    $np_row->cache = $source;
                    $np_row->update();
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

    /**
     * @return NowPlaying[]
     */
    public function getAllPublic(): array
    {
        return array_values(
            array_filter(
                $this->np,
                static function (NowPlaying $np_row) {
                    return $np_row->station->is_public;
                }
            )
        );
    }

    public function getForStation(
        int|string|Station|null $stationId
    ): ?NowPlaying {
        if ($stationId instanceof Station) {
            $stationId = $stationId->getId();
        }

        if (null === $stationId) {
            return null;
        }

        foreach ($this->np as $npRow) {
            if ($npRow->station->id === (int)$stationId || $npRow->station->shortcode === $stationId) {
                return $npRow;
            }
        }
        return null;
    }
}
