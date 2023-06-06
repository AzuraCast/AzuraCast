<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Radio\AutoDJ\Annotations;

final class NextSongCommand extends AbstractCommand
{
    public function __construct(
        private readonly Annotations $annotations
    ) {
    }

    protected function doRun(
        Entity\Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): string|bool {
        return $this->annotations->annotateNextSong(
            $station,
            $asAutoDj
        );
    }
}
