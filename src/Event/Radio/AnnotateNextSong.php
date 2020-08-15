<?php
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
    protected Entity\Station $station;

    protected ?Entity\StationQueue $queue;

    protected ?Entity\StationMedia $media;

    protected ?Entity\StationPlaylist $playlist;

    protected ?Entity\StationRequest $request;

    protected ?string $songPath;

    /** @var bool Whether the request is going to the AutoDJ or being used for testing. */
    protected bool $asAutoDj = false;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    protected array $annotations = [];

    public function __construct(
        Entity\Station $station,
        ?Entity\StationQueue $queue = null,
        ?Entity\StationMedia $media = null,
        ?Entity\StationPlaylist $playlist = null,
        ?Entity\StationRequest $request = null,
        bool $asAutoDj = false
    ) {
        $this->station = $station;

        $this->queue = $queue;
        $this->media = $media;
        $this->playlist = $playlist;
        $this->request = $request;

        $this->asAutoDj = $asAutoDj;
    }

    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    public function getQueue(): ?Entity\StationQueue
    {
        return $this->queue;
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
     *
     * @return string
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

    public static function fromQueue(
        Entity\StationQueue $queue,
        bool $asAutoDj = false
    ): self {
        return new self(
            $queue->getStation(),
            $queue,
            $queue->getMedia(),
            $queue->getPlaylist(),
            $queue->getRequest(),
            $asAutoDj
        );
    }

    public static function fromRequest(
        Entity\StationRequest $request,
        bool $asAutoDj = false
    ): self {
        return new self(
            $request->getStation(),
            null,
            $request->getTrack(),
            null,
            $request,
            $asAutoDj
        );
    }
}
