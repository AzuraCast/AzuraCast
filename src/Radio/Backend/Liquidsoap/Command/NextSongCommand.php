<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Radio\AutoDJ\Annotations;
use App\Entity\Station;

final class NextSongCommand extends AbstractCommand
{
    public function __construct(
        private readonly Annotations $annotations
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): string|bool {
        return $this->annotations->annotateNextSong(
            $station,
            $asAutoDj
        );
    }
}
