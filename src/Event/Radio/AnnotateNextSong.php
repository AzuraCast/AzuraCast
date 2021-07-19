<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered every time the next-playing song is preparing to be annotated for delivery to Liquidsoap.
 *
 * @package App\Event\Radio
 */
class AnnotateNextSong extends Event
{
    protected ?string $songPath;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    protected array $annotations = [];

    public function __construct(
        protected Entity\StationQueue $queue,
        protected bool $asAutoDj = false
    ) {
    }

    public function getQueue(): ?Entity\StationQueue
    {
        return $this->queue;
    }

    public function getStation(): Entity\Station
    {
        return $this->queue->getStation();
    }

    public function getMedia(): ?Entity\StationMedia
    {
        return $this->queue->getMedia();
    }

    public function getPlaylist(): ?Entity\StationPlaylist
    {
        return $this->queue->getPlaylist();
    }

    public function getRequest(): ?Entity\StationRequest
    {
        return $this->queue->getRequest();
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
            return '';
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
}
