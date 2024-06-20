<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\PodcastSources;
use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use Carbon\CarbonImmutable;

final class CheckPodcastPlaylistsTask extends AbstractTask
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems,
        private readonly PodcastEpisodeRepository $podcastEpisodeRepo
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '*/10 * * * *';
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            $this->syncPodcastPlaylists($station);
        }
    }

    public function syncPodcastPlaylists(Station $station): void
    {
        $this->logger->info(
            'Processing playlist-based podcasts for station...',
            [
                'station' => $station->getName(),
            ]
        );

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);
        $fsPodcasts = $this->stationFilesystems->getPodcastsFilesystem($station);

        $podcasts = $this->em->createQuery(
            <<<'DQL'
                SELECT p, sp
                FROM App\Entity\Podcast p
                JOIN p.playlist sp
                WHERE p.source = :source
            DQL
        )->setParameter('source', PodcastSources::Playlist->value)
            ->execute();

        $mediaInPlaylistQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT spm.media_id
                FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist = :playlist
            DQL
        );

        $mediaInPodcastQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT pe.id, pe.playlist_media_id
                FROM App\Entity\PodcastEpisode pe
                WHERE pe.podcast = :podcast
            DQL
        );

        $stats = [
            'added' => 0,
            'removed' => 0,
            'unchanged' => 0,
        ];

        /** @var Podcast $podcast */
        foreach ($podcasts as $podcast) {
            $playlist = $podcast->getPlaylist();

            $mediaInPlaylist = array_column(
                $mediaInPlaylistQuery->setParameter('playlist', $playlist)->getArrayResult(),
                'media_id',
                'media_id'
            );

            $mediaInPodcast = array_column(
                $mediaInPodcastQuery->setParameter('podcast', $podcast)->getArrayResult(),
                'id',
                'playlist_media_id'
            );

            $mediaToAdd = [];
            foreach ($mediaInPlaylist as $mediaId) {
                if (isset($mediaInPodcast[$mediaId])) {
                    $stats['unchanged']++;
                    unset($mediaInPodcast[$mediaId]);
                } else {
                    $mediaToAdd[] = $mediaId;
                }
            }

            foreach ($mediaToAdd as $mediaId) {
                $media = $this->em->find(StationMedia::class, $mediaId);

                if ($media instanceof StationMedia) {
                    // Create new podcast episode.
                    $podcastEpisode = new PodcastEpisode($podcast);
                    $podcastEpisode->setPlaylistMedia($media);

                    $podcastEpisode->setExplicit(false);

                    $podcastEpisode->setTitle($media->getTitle() ?? 'Untitled Episode');
                    $podcastEpisode->setDescription(
                        implode("\n", array_filter([
                            $media->getArtist(),
                            $media->getAlbum(),
                            $media->getLyrics(),
                        ]))
                    );

                    $publishAt = CarbonImmutable::createFromTimestamp($media->getMtime(), 'UTC');
                    if (!$podcast->playlistAutoPublish()) {
                        // Set a date in the future to unpublish the episode.
                        $podcastEpisode->setPublishAt(
                            $publishAt->addYears(10)->getTimestamp()
                        );
                    } else {
                        $podcastEpisode->setPublishAt($publishAt->getTimestamp());
                    }

                    $this->em->persist($podcastEpisode);
                    $this->em->flush();

                    $artPath = StationMedia::getArtPath($media->getUniqueId());
                    if ($fsMedia->fileExists($artPath)) {
                        $art = $fsMedia->read($artPath);
                        $this->podcastEpisodeRepo->writeEpisodeArt($podcastEpisode, $art);
                    }

                    $stats['added']++;
                }
            }

            // Remove remaining media that doesn't match.
            foreach ($mediaInPodcast as $episodeId) {
                $episode = $this->em->find(PodcastEpisode::class, $episodeId);

                if ($episode instanceof PodcastEpisode) {
                    $this->podcastEpisodeRepo->delete($episode, $fsPodcasts);
                }

                $stats['removed']++;
            }
        }

        $this->logger->debug(
            'Playlist-based podcasts for station processed.',
            [
                'station' => $station->getName(),
                'stats' => $stats,
            ]
        );
    }
}
