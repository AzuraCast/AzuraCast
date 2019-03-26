<?php
namespace App\Event\Radio;

use App\Entity;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event triggered every time the next-playing song is preparing to be annotated for delivery to Liquidsoap.
 *
 * @package App\Event\Radio
 */
class AnnotateNextSong extends Event
{
    public const NAME = 'radio-liquidsoap-annotate-next-song';

    /** @var null|string|Entity\SongHistory The next song, if it's already calculated. */
    protected $next_song;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    protected $annotations = [];

    /** @var string The path of the song to  */
    protected $song_path;

    /** @var Entity\Station */
    protected $station;

    public function __construct(Entity\Station $station, $next_song = null)
    {
        $this->station = $station;
        $this->next_song = $next_song;
    }

    /**
     * @return Entity\Station
     */
    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    /**
     * @return string|Entity\SongHistory|null
     */
    public function getNextSong()
    {
        return $this->next_song;
    }

    /**
     * @param array $annotations
     */
    public function setAnnotations(array $annotations): void
    {
        $this->annotations = $annotations;
    }

    /**
     * @param array $annotations
     */
    public function addAnnotations(array $annotations): void
    {
        $this->annotations = array_merge($this->annotations, $annotations);
    }

    /**
     * @param string $song_path
     */
    public function setSongPath(string $song_path): void
    {
        $this->song_path = $song_path;
    }

    /**
     * Compile the resulting annotations into one string for Liquidsoap to consume.
     *
     * @return string
     */
    public function buildAnnotations(): string
    {
        $this->annotations = array_filter($this->annotations);

        if (!empty($this->annotations)) {
            $annotations_str = [];
            foreach($this->annotations as $annotation_key => $annotation_val) {
                $annotations_str[] = $annotation_key.'="'.$annotation_val.'"';
            }

            return 'annotate:'.implode(',', $annotations_str).':'.$this->song_path;
        }

        return $this->song_path;
    }
}
