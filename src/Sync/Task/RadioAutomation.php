<?php
namespace App\Sync\Task;

use App\Entity;
use App\Radio\Adapters;
use Azura\Exception;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;

class RadioAutomation extends AbstractTask
{
    public const DEFAULT_THRESHOLD_DAYS = 14;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Adapters $adapters;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Adapters $adapters
    ) {
        parent::__construct($em, $settingsRepo);

        $this->mediaRepo = $mediaRepo;
        $this->adapters = $adapters;
    }

    /**
     * Iterate through all stations and attempt to run automated assignment.
     *
     * @param bool $force
     */
    public function run($force = false): void
    {
        // Check all stations for automation settings.
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        $automation_log = $this->settingsRepo->getSetting('automation_log', []);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            try {
                if ($this->runStation($station)) {
                    $automation_log[$station->getId()] = $station->getName() . ': SUCCESS';
                }
            } catch (Exception $e) {
                $automation_log[$station->getId()] = $station->getName() . ': ERROR - ' . $e->getMessage();
            }
        }

        $this->settingsRepo->setSetting('automation_log', $automation_log);
    }

    /**
     * Run automated assignment (if enabled) for a given $station.
     *
     * @param Entity\Station $station
     * @param bool $force
     *
     * @return bool
     * @throws Exception
     */
    public function runStation(Entity\Station $station, $force = false)
    {
        $settings = (array)$station->getAutomationSettings();

        if (empty($settings)) {
            throw new Exception('Automation has not been configured for this station yet.');
        }

        if (!$settings['is_enabled']) {
            throw new Exception('Automation is not enabled for this station.');
        }

        // Check whether assignment needs to be run.
        $threshold_days = (int)$settings['threshold_days'];
        $threshold = time() - (86400 * $threshold_days);

        if (!$force && $station->getAutomationTimestamp() >= $threshold) {
            return false;
        } // No error, but no need to run assignment.

        $playlists = [];
        $original_playlists = [];

        // Related playlists are already automatically sorted by weight.
        $i = 0;

        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */

            if ($playlist->getIsEnabled() &&
                $playlist->getType() == Entity\StationPlaylist::TYPE_DEFAULT &&
                $playlist->getIncludeInAutomation()
            ) {
                // Clear all related media.
                foreach ($playlist->getMediaItems() as $media_item) {
                    $media = $media_item->getMedia();
                    $song = $media->getSong();
                    if ($song instanceof Entity\Song) {
                        $original_playlists[$song->getId()][] = $i;
                    }

                    $this->em->remove($media_item);
                }

                $playlists[$i] = $playlist;

                $i++;
            }
        }

        if (count($playlists) == 0) {
            throw new Exception('No playlists have automation enabled.');
        }

        $this->em->flush();

        $media_report = $this->generateReport($station, $threshold_days);

        $media_report = array_filter($media_report, function ($media) use ($original_playlists) {
            // Remove songs that are already in non-auto-assigned playlists.
            if (!empty($media['playlists'])) {
                return false;
            }

            // Remove songs that weren't already in auto-assigned playlists.
            if (!isset($original_playlists[$media['song_id']])) {
                return false;
            }

            return true;
        });

        // Place all songs with 0 plays back in their original playlists.
        foreach ($media_report as $song_id => $media) {
            if ($media['num_plays'] == 0 && isset($original_playlists[$song_id])) {
                $media_row = $media['record'];

                foreach ($original_playlists[$song_id] as $playlist_key) {
                    $spm = new Entity\StationPlaylistMedia($playlists[$playlist_key], $media_row);
                    $this->em->persist($spm);
                }

                unset($media_report[$song_id]);
            }
        }

        $this->em->flush();

        // Sort songs by ratio descending.
        uasort($media_report, function ($a_media, $b_media) {
            $a = (int)$a_media['ratio'];
            $b = (int)$b_media['ratio'];

            return ($a < $b) ? 1 : (($a > $b) ? -1 : 0);
        });

        // Distribute media across the enabled playlists and assign media to playlist.
        $num_songs = count($media_report);
        $num_playlists = count($playlists);
        $songs_per_playlist = floor($num_songs / $num_playlists);

        $i = 0;

        foreach ($playlists as $playlist) {
            if ($i == 0) {
                $playlist_num_songs = $songs_per_playlist + ($num_songs % $num_playlists);
            } else {
                $playlist_num_songs = $songs_per_playlist;
            }

            $media_in_playlist = array_slice($media_report, $i, $playlist_num_songs);
            foreach ($media_in_playlist as $media) {
                $spm = new Entity\StationPlaylistMedia($playlist, $media['record']);
                $this->em->persist($spm);
            }

            $i += $playlist_num_songs;
        }

        $station->setAutomationTimestamp(time());
        $this->em->persist($station);
        $this->em->flush();

        // Write new PLS playlist configuration.
        $backend_adapter = $this->adapters->getBackendAdapter($station);
        $backend_adapter->write($station);

        return true;
    }

    /**
     * Generate a Performance Report for station $station's songs over the last $threshold_days days.
     *
     * @param Entity\Station $station
     * @param int $threshold_days
     *
     * @return array
     */
    public function generateReport(Entity\Station $station, $threshold_days = self::DEFAULT_THRESHOLD_DAYS)
    {
        $threshold = Chronos::now()->subDays((int)$threshold_days)->getTimestamp();

        // Pull all SongHistory data points.
        $data_points_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sh.song_id, sh.timestamp_start, sh.delta_positive, sh.delta_negative, sh.listeners_start 
            FROM App\Entity\SongHistory sh 
            WHERE sh.station_id = :station_id 
            AND sh.timestamp_end != 0 
            AND sh.timestamp_start >= :threshold')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $total_plays = 0;
        $data_points = [];

        foreach ($data_points_raw as $row) {
            $total_plays++;

            if (!isset($data_points[$row['song_id']])) {
                $data_points[$row['song_id']] = [];
            }

            $data_points[$row['song_id']][] = $row;
        }

        $media_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sm, spm, sp 
            FROM App\Entity\StationMedia sm 
            LEFT JOIN sm.playlists spm 
            LEFT JOIN spm.playlist sp 
            WHERE sm.station_id = :station_id 
            ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->execute();

        $report = [];

        foreach ($media_raw as $row_obj) {
            /** @var Entity\StationMedia $row_obj */
            $row = $this->mediaRepo->toArray($row_obj);

            $media = [
                'song_id' => $row['song_id'],
                'record' => $row_obj,

                'title' => $row['title'],
                'artist' => $row['artist'],
                'length_raw' => $row['length'],
                'length' => $row['length_text'],
                'path' => $row['path'],

                'playlists' => [],
                'data_points' => [],

                'num_plays' => 0,
                'percent_plays' => 0,

                'delta_negative' => 0,
                'delta_positive' => 0,
                'delta_total' => 0,

                'ratio' => 0,
            ];

            if ($row_obj->getPlaylists()->count() > 0) {
                foreach ($row_obj->getPlaylists() as $playlist_item) {
                    /** @var Entity\StationPlaylistMedia $playlist_item */
                    $playlist = $playlist_item->getPlaylist();
                    $media['playlists'][] = $playlist->getName();
                }
            }

            if (isset($data_points[$row['song_id']])) {
                $ratio_points = [];

                foreach ($data_points[$row['song_id']] as $data_row) {
                    $media['num_plays']++;

                    $media['delta_positive'] += $data_row['delta_positive'];
                    $media['delta_negative'] -= $data_row['delta_negative'];

                    /*
                     * The song ratio is determined by the total impact in listenership the song caused (both up and down)
                     * over its play time, divided by the number of listeners the song started with. Impacts are weighted
                     * higher for more significant percentage impacts up or down.
                     *
                     * i.e.
                     * 1 listener at start, gained 3 listeners => 3/1*100 = 300
                     * 100 listeners at start, lost 15 listeners => -15/100*100 = -15
                     */

                    $delta_total = $data_row['delta_positive'] - $data_row['delta_negative'];
                    $ratio_points[] = ($data_row['listeners_start'] == 0) ? 0 : ($delta_total / $data_row['listeners_start']) * 100;
                }

                $media['delta_total'] = $media['delta_positive'] + $media['delta_negative'];
                $media['percent_plays'] = round(($media['num_plays'] / $total_plays) * 100, 2);

                $media['ratio'] = round(array_sum($ratio_points) / count($ratio_points), 3);
            }

            $report[$row['song_id']] = $media;
        }

        return $report;
    }

}
