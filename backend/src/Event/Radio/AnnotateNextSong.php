<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered every time the next-playing song is preparing to be annotated for delivery to Liquidsoap.
 */
final class AnnotateNextSong extends Event
{
    public const array ALLOWED_ANNOTATIONS = [
        'title',
        'artist',
        'duration',
        'song_id',
        'media_id',
        'playlist_id',
        'jingle_mode',
        'request_id',
        'sq_id',
        'liq_amplify',
        'azuracast_autocue',
        'azuracast_cache_key',
        'autocue_cue_in',
        'autocue_cue_out',
        'autocue_fade_in',
        'autocue_fade_out',
        'autocue_start_next',
    ];

    public const array ALWAYS_STRING_ANNOTATIONS = [
        'title',
        'artist',
    ];

    private ?string $songPath = null;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    private array $annotations = [];

    public function __construct(
        private readonly Station $station,
        private readonly ?StationQueue $queue = null,
        private readonly ?StationMedia $media = null,
        private readonly ?StationPlaylist $playlist = null,
        private readonly ?StationRequest $request = null,
        private readonly bool $asAutoDj = false
    ) {
    }

    public function getQueue(): ?StationQueue
    {
        return $this->queue;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getMedia(): ?StationMedia
    {
        return $this->media;
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function getRequest(): ?StationRequest
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

        if (!empty($this->annotations)) {
            $annotateParts = [
                'annotate',
                ConfigWriter::annotateArray($this->annotations),
                $this->songPath,
            ];

            return implode(':', $annotateParts);
        }

        return $this->songPath;
    }

    public static function fromStationMedia(
        Station $station,
        StationMedia $media,
        bool $asAutoDj = false
    ): self {
        return new self(
            station: $station,
            media: $media,
            asAutoDj: $asAutoDj
        );
    }

    public static function fromStationQueue(
        StationQueue $queue,
        bool $asAutoDj = false
    ): self {
        return new self(
            station: $queue->station,
            queue: $queue,
            media: $queue->media,
            playlist: $queue->playlist,
            request: $queue->request,
            asAutoDj: $asAutoDj
        );
    }
}
