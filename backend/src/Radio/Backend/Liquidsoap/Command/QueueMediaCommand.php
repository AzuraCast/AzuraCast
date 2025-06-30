<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationMediaRepository;
use App\Entity\Station;
use App\Radio\Backend\Liquidsoap;
use RuntimeException;

final class QueueMediaCommand extends AbstractCommand
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
        $mediaIds = $payload['media_ids'] ?? [];
        if (empty($mediaIds)) {
            throw new RuntimeException('No media_ids provided.');
        }

        $queued = 0;
        foreach ($mediaIds as $mediaId) {
            $media = $this->mediaRepo->findByUniqueId($mediaId, $station);
            if ($media) {
                $mediaPath = 'media:' . $media->getPath();
                $this->liquidsoap->command($station, sprintf('request.queue.push %s', $mediaPath));
                $queued++;
            }
        }

        return [
            'success' => true,
            'message' => sprintf('%d tracks queued', $queued),
        ];
    }
}
