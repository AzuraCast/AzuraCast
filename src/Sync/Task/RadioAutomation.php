<?php

namespace App\Sync\Task;

use App\Entity;
use App\Exception;
use App\Radio\Adapters;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Log\LoggerInterface;

class RadioAutomation extends AbstractTask
{
    public const DEFAULT_THRESHOLD_DAYS = 14;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Adapters $adapters;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Adapters $adapters
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->mediaRepo = $mediaRepo;
        $this->adapters = $adapters;
    }

    /**
     * Iterate through all stations and attempt to run automated assignment.
     *
     * @param bool $force
     */
    public function run(bool $force = false): void
    {
        // Check all stations for automation settings.
        // Use this to avoid detached entity errors.
        $stations = SimpleBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s'),
            1
        );

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            try {
                if ($this->runStation($station)) {
                    $this->logger->info('Automated assignment [' . $station->getName() . ']: Successfully run.');
                } else {
                    $this->logger->info('Automated assignment [' . $station->getName() . ']: Skipped.');
                }
            } catch (Exception $e) {
                $this->logger->error('Automated assignment [' . $station->getName() . ']: Error: ' . $e->getMessage());
            }
        }
    }

    public function runStation(Entity\Station $station, bool $force = false): bool
    {
        $settings = (array)$station->getAutomationSettings();

        if (empty($settings) || !$settings['is_enabled']) {
            return false;
        }

        // Check whether assignment needs to be run.
        $threshold_days = (int)$settings['threshold_days'];
        $threshold = CarbonImmutable::now('UTC')
            ->subDays($threshold_days)
            ->getTimestamp();

        if (!$force && $station->getAutomationTimestamp() >= $threshold) {
            return false;
        } // No error, but no need to run assignment.


        // Pull songs in current playlists, then clear those playlists.
        $getSongsInPlaylistQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sm.id
            FROM App\Entity\StationPlaylistMedia spm
            JOIN spm.media sm
            WHERE spm.playlist = :playlist');

        $mediaToUpdate = [];
        $playlists = [];

        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if (
                $playlist->getIsEnabled()
                && $playlist->getType() === Entity\StationPlaylist::TYPE_DEFAULT
                && $playlist->getIncludeInAutomation()
            ) {
                $playlists[] = $playlist->getId();

                // Clear all related media.
                $mediaInPlaylist = $getSongsInPlaylistQuery->setParameter('playlist', $playlist)
                    ->getArrayResult();

                foreach ($mediaInPlaylist as $media) {
                    $mediaToUpdate[$media['id']] = [
                        'old_playlist_id' => $playlist->getId(),
                        'new_playlist_id' => $playlist->getId(),
                    ];
                }
            }
        }

        if (0 === count($playlists)) {
            throw new Exception('No playlists have automation enabled.');
        }

        // Generate the actual report for listenership.
        $mediaReport = $this->generateReport($station, $threshold_days);

        // Remove songs that weren't already in auto-assigned playlists.
        $mediaReport = array_filter($mediaReport, function ($media) use ($mediaToUpdate) {
            return (isset($mediaToUpdate[$media['id']]));
        });

        // Place all songs with 0 plays back in their original playlists.
        foreach ($mediaReport as $song_id => $media) {
            if ($media['num_plays'] === 0 && isset($original_playlists[$song_id])) {
                unset($mediaToUpdate[$media['id']], $mediaReport[$song_id]);
            }
        }

        // Sort songs by ratio descending.
        uasort($mediaReport, function ($a_media, $b_media) {
            return (int)$b_media['ratio'] <=> (int)$a_media['ratio'];
        });

        // Distribute media across the enabled playlists and assign media to playlist.
        $numSongs = count($mediaReport);
        $numPlaylists = count($playlists);

        $songsPerPlaylist = (int)floor($numSongs / $numPlaylists);

        $i = 0;
        foreach ($playlists as $playlistId) {
            if ($i === 0) {
                $playlistNumSongs = $songsPerPlaylist + ($numSongs % $numPlaylists);
            } else {
                $playlistNumSongs = $songsPerPlaylist;
            }

            $media_in_playlist = array_slice($mediaReport, $i, $playlistNumSongs);
            foreach ($media_in_playlist as $media) {
                $mediaToUpdate[$media['id']]['new_playlist_id'] = $playlistId;
            }

            $i += $playlistNumSongs;
        }

        // Update media playlist placement.
        $updateMediaPlaylistQuery = $this->em->createQuery(/** @lang DQL */ 'UPDATE
            App\Entity\StationPlaylistMedia spm
            SET spm.playlist_id = :new_playlist_id
            WHERE spm.playlist_id = :old_playlist_id
            AND spm.media_id = :media_id');

        foreach ($mediaToUpdate as $mediaId => $playlists) {
            $updateMediaPlaylistQuery->setParameter('media_id', $mediaId)
                ->setParameter('old_playlist_id', $playlists['old_playlist_id'])
                ->setParameter('new_playlist_id', $playlists['new_playlist_id'])
                ->execute();
        }

        $this->em->clear();

        /** @var Entity\Station $station */
        $station = $this->em->find(Entity\Station::class, $station->getId());

        $station->setAutomationTimestamp(time());
        $this->em->persist($station);
        $this->em->flush();

        // Write new PLS playlist configuration.
        $backend_adapter = $this->adapters->getBackendAdapter($station);
        $backend_adapter->write($station);

        return true;
    }

    /**
     * @return mixed[]
     */
    public function generateReport(
        Entity\Station $station,
        int $threshold_days = self::DEFAULT_THRESHOLD_DAYS
    ): array {
        $threshold = CarbonImmutable::now()
            ->subDays($threshold_days)
            ->getTimestamp();

        // Pull all SongHistory data points.
        $dataPointsRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sh.song_id, sh.timestamp_start, sh.delta_positive, sh.delta_negative, sh.listeners_start
            FROM App\Entity\SongHistory sh
            WHERE sh.station = :station
            AND sh.timestamp_end != 0
            AND sh.timestamp_start >= :threshold')
            ->setParameter('station', $station)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $total_plays = 0;
        $data_points = [];

        foreach ($dataPointsRaw as $row) {
            $total_plays++;

            if (!isset($data_points[$row['song_id']])) {
                $data_points[$row['song_id']] = [];
            }

            $data_points[$row['song_id']][] = $row;
        }

        $mediaQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sm
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('storageLocation', $station->getMediaStorageLocation());

        $iterator = SimpleBatchIteratorAggregate::fromQuery($mediaQuery, 100);
        $report = [];

        foreach ($iterator as $row) {
            /** @var Entity\StationMedia $row */
            $songId = $row->getSongId();

            $media = [
                'id' => $row->getId(),
                'song_id' => $songId,

                'title' => $row->getTitle(),
                'artist' => $row->getArtist(),
                'length_raw' => $row->getLength(),
                'length' => $row->getLengthText(),
                'path' => $row->getPath(),

                'playlists' => [],
                'data_points' => [],

                'num_plays' => 0,
                'percent_plays' => 0,

                'delta_negative' => 0,
                'delta_positive' => 0,
                'delta_total' => 0,

                'ratio' => 0,
            ];

            if ($row->getPlaylists()->count() > 0) {
                foreach ($row->getPlaylists() as $playlist_item) {
                    /** @var Entity\StationPlaylistMedia $playlist_item */
                    $playlist = $playlist_item->getPlaylist();
                    $media['playlists'][] = $playlist->getName();
                }
            }

            if (isset($data_points[$songId])) {
                $ratio_points = [];

                foreach ($data_points[$songId] as $data_row) {
                    $media['num_plays']++;

                    $media['delta_positive'] += $data_row['delta_positive'];
                    $media['delta_negative'] -= $data_row['delta_negative'];

                    /*
                     * The song ratio is determined by the total impact in listenership the song caused
                     * (both up and down) over its play time, divided by the number of listeners the song started
                     * with. Impacts are weighted higher for more significant percentage impacts up or down.
                     *
                     * i.e.
                     * 1 listener at start, gained 3 listeners => 3/1*100 = 300
                     * 100 listeners at start, lost 15 listeners => -15/100*100 = -15
                     */

                    $delta_total = $data_row['delta_positive'] - $data_row['delta_negative'];
                    $ratio_points[] = ($data_row['listeners_start'] == 0)
                        ? 0
                        : ($delta_total / $data_row['listeners_start']) * 100;
                }

                $media['delta_total'] = $media['delta_positive'] + $media['delta_negative'];
                $media['percent_plays'] = round(($media['num_plays'] / $total_plays) * 100, 2);

                $media['ratio'] = round(array_sum($ratio_points) / count($ratio_points), 3);
            }

            $report[$songId] = $media;
        }

        return $report;
    }
}
