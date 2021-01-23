<?php

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Event\Media\GetAlbumArt;
use App\Exception\RateLimitExceededException;
use App\RateLimit;
use Psr\Log\LoggerInterface;

abstract class AbstractAlbumArtHandler
{
    protected LoggerInterface $logger;

    protected RateLimit $rateLimit;

    public function __construct(LoggerInterface $logger, RateLimit $rateLimit)
    {
        $this->logger = $logger;
        $this->rateLimit = $rateLimit;
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

        // Check rate limit
        try {
            $this->checkRateLimit($serviceName);
        } catch (RateLimitExceededException $e) {
            $this->logger->error(
                sprintf('%s Album Art Error: Rate limit exceeded. Skipping request.', $serviceName)
            );
            return;
        }

        $song = $event->getSong();

        try {
            $albumArt = $this->getAlbumArt($song);
            if (!empty($albumArt)) {
                $event->setAlbumArt($albumArt);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('%s Album Art Error: %s', $serviceName, $e->getMessage()),
                [
                    'exception' => $e,
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );

            if (false !== stripos($e->getMessage(), 'rate limit')) {
                return;
            }

            throw new \RuntimeException(
                sprintf('%s Album Art Error: %s', $serviceName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    protected function checkRateLimit(string $serviceName, int $maxIterations = 5, int $iteration = 1): void
    {
        try {
            $this->rateLimit->checkRateLimit($serviceName, 1, 2);
        } catch (RateLimitExceededException $e) {
            if ($iteration >= $maxIterations) {
                throw $e;
            }

            usleep(1100000); // 1.1 seconds
            $this->checkRateLimit($serviceName, $maxIterations, $iteration + 1);
        }
    }

    protected function isSupported(): bool
    {
        return true;
    }

    abstract protected function getServiceName(): string;

    abstract protected function getAlbumArt(Entity\SongInterface $song): ?string;
}
