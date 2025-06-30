<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationMediaRepository;
use App\Entity\Station;
use App\Radio\Backend\Liquidsoap;
use RuntimeException;

final class PlayMediaCommand extends AbstractCommand
{
    public function __construct(
        private readonly Liquidsoap $liquidsoap,
        private readonly StationMediaRepository $mediaRepo,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): mixed {
        $mediaId = $payload['media_id'] ?? null;
        if (!$mediaId) {
            throw new RuntimeException('No media_id provided.');
        }

        $media = $this->mediaRepo->findByUniqueId($mediaId, $station);
        if (!$media) {
            throw new RuntimeException('Media not found.');
        }

        $mediaPath = 'media:' . $media->getPath();
        
        // Queue the file
        $this->liquidsoap->command($station, sprintf('request.queue.push %s', $mediaPath));
        
        // If immediate, skip current song
        if ($payload['immediate'] ?? false) {
            $this->liquidsoap->skip($station);
        }

        return [
            'success' => true,
            'message' => ($payload['immediate'] ?? false) ? 'Playing immediately' : 'Queued for playback',
        ];
    }
}
