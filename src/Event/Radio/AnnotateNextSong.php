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
    /** @var null|Entity\SongHistory The next song, if it's already calculated. */
    protected ?Entity\SongHistory $nextSong;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    protected array $annotations = [];

    /** @var string The path of the song to annotate. */
    protected string $songPath;

    protected Entity\Station $station;

    public function __construct(Entity\Station $station, ?Entity\SongHistory $next_song = null)
    {
        $this->station = $station;
        $this->nextSong = $next_song;
    }

    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    public function getNextSong(): ?Entity\SongHistory
    {
        return $this->nextSong;
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
}
