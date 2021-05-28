<?php

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Event\Media\GetAlbumArt;
use App\Exception\RateLimitExceededException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

abstract class AbstractAlbumArtHandler
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function __invoke(GetAlbumArt $event): void
    {
        $serviceName = $this->getServiceName();

        if (!$this->isSupported()) {
            $this->logger->info(
                sprintf(
                    'Service %f is not currently supported; skipping album art check.',
                    $serviceName
                )
            );
            return;
        }

        $song = $event->getSong();

        try {
            $albumArt = $this->getAlbumArt($song);
            if (!empty($albumArt)) {
                $event->setAlbumArt($albumArt);
            }
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('%s Album Art Error: %s', $serviceName, $e->getMessage()),
                [
                    'exception' => $e,
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );

            if (
                $e instanceof RateLimitExceededException
                || false !== stripos($e->getMessage(), 'rate limit')
            ) {
                return;
            }

            throw new RuntimeException(
                sprintf('%s Album Art Error: %s', $serviceName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    protected function isSupported(): bool
    {
        return true;
    }

    abstract protected function getServiceName(): string;

    abstract protected function getAlbumArt(Entity\Interfaces\SongInterface $song): ?string;
}
