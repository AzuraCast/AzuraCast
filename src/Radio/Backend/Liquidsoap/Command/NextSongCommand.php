<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Radio\AutoDJ\Annotations;
use App\Radio\FallbackFile;
use Monolog\Logger;

class NextSongCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        protected Annotations $annotations,
        protected FallbackFile $fallbackFile
    ) {
        parent::__construct($logger);
    }

    protected function doRun(
        Entity\Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): string {
        return $this->annotations->annotateNextSong(
            $station,
            $asAutoDj
        );
    }
}
