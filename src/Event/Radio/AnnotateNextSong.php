<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered every time the next-playing song is preparing to be annotated for delivery to Liquidsoap.
 *
 * @package App\Event\Radio
 */
final class AnnotateNextSong extends Event
{
    private ?string $songPath;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    private array $annotations = [];

    public function __construct(
        private readonly Entity\Station $station,
        private readonly ?Entity\StationQueue $queue = null,
        private readonly ?Entity\StationMedia $media = null,
        private readonly ?Entity\StationPlaylist $playlist = null,
        private readonly ?Entity\StationRequest $request = null,
        private readonly bool $asAutoDj = false
    ) {
    }

    public function getQueue(): ?Entity\StationQueue
    {
        return $this->queue;
    }

    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    public function getMedia(): ?Entity\StationMedia
    {
        return $this->media;
    }

    public function getPlaylist(): ?Entity\StationPlaylist
    {
        return $this->playlist;
    }

    public function getRequest(): ?Entity\StationRequest
    {
        return $this->request;
    }

    public function setAnnotations(array $annotations): void
    {
        $this->annotations = $annotations;
    }

    public function addAnnotations(array $annotations): void
    {
        $this->annotations = array_merge($this->annotations, $annotations);
    }

    public function setSongPath(string $songPath): void
    {
        $this->songPath = $songPath;
    }

    public function isAsAutoDj(): bool
    {
        return $this->asAutoDj;
    }

    /**
     * Compile the resulting annotations into one string for Liquidsoap to consume.
     */
    public function buildAnnotations(): string
    {
        if (empty($this->songPath)) {
            throw new RuntimeException('No valid path for song.');
        }

        $this->annotations = array_filter($this->annotations);

        if (!empty($this->annotations)) {
            $annotations_str = [];
            foreach ($this->annotations as $annotation_key => $annotation_val) {
                $annotations_str[] = $annotation_key . '="' . $annotation_val . '"';
            }

            return 'annotate:' . implode(',', $annotations_str) . ':' . $this->songPath;
        }

        return $this->songPath;
    }

    public static function fromStationMedia(
        Entity\Station $station,
        Entity\StationMedia $media,
        bool $asAutoDj = false
    ): self {
        return new self(
            station: $station,
            media: $media,
            asAutoDj: $asAutoDj
        );
    }

    public static function fromStationQueue(
        Entity\StationQueue $queue,
        bool $asAutoDj = false
    ): self {
        return new self(
            station: $queue->getStation(),
            queue: $queue,
            media: $queue->getMedia(),
            playlist: $queue->getPlaylist(),
            request: $queue->getRequest(),
            asAutoDj: $asAutoDj
        );
    }
}
