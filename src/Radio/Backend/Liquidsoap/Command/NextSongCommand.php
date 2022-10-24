<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Radio\AutoDJ\Annotations;
use Monolog\Logger;

final class NextSongCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        private readonly Annotations $annotations
    ) {
        parent::__construct($logger);
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
