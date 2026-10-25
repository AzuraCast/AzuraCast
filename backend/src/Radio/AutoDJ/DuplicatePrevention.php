<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\LoggerAwareTrait;
use App\Entity\Api\StationPlaylistQueue;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * @phpstan-type PlayedTrack array{
 *     song_id: string,
 *     text: string|null,
 *     artist: string|null,
 *     title: string|null,
 *     timestamp_played: CarbonImmutable|int
 * }
 */
final class DuplicatePrevention
{
    use LoggerAwareTrait;

    public const array ARTIST_SEPARATORS = [
        ', ',
        ' feat ',
        ' feat. ',
        ' ft ',
        ' ft. ',
        ' / ',
        ' & ',
        ' vs. ',
    ];

    /**
     * @param StationPlaylistQueue[] $eligibleTracks
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    public function preventDuplicates(
        array $eligibleTracks,
        RecentSongHistory $recentSongHistory,
        bool $allowDuplicates = false
    ): ?StationPlaylistQueue {
        if (empty($eligibleTracks)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $playedTracks = $recentSongHistory->playedTracks;
        $artistPlayedTracks = $recentSongHistory->artistPlayedTracks;

        $latestSongIdsPlayed = [];

        foreach ($playedTracks as $playedTrack) {
            $songId = $playedTrack['song_id'];

            $timestampPlayed = $playedTrack['timestamp_played'];
            if ($timestampPlayed instanceof DateTimeInterface) {
                $timestampPlayed = $timestampPlayed->getTimestamp();
            }

            $latestSongIdsPlayed[$songId] = max(
                $latestSongIdsPlayed[$songId] ?? 0,
                $timestampPlayed
            );
        }

        /** @var StationPlaylistQueue[] $notPlayedEligibleTracks */
        $notPlayedEligibleTracks = [];

        foreach ($eligibleTracks as $mediaId => $track) {
            $songId = $track->song_id;

            if (isset($latestSongIdsPlayed[$songId])) {
                $track->last_played = $latestSongIdsPlayed[$songId];
                continue;
            }

            $notPlayedEligibleTracks[$mediaId] = $track;
        }

        $validTrack = $this->getDistinctTrack($notPlayedEligibleTracks, $playedTracks, $artistPlayedTracks)
            ?? $this->getDistinctTrack($eligibleTracks, $playedTracks, $artistPlayedTracks);

        if (null !== $validTrack) {
            $this->logger->info(
                'Found track that avoids duplicate title and artist.',
                [
                    'media_id' => $validTrack->media_id,
                    'title' => $validTrack->title,
                    'artist' => $validTrack->artist,
                ]
            );

            return $validTrack;
        }

        // If we reach this point, there's no way to avoid a duplicate title and artist.
        $unavoidableDuplicate = $this->hasTrackAvoidingDuplicateTitle($notPlayedEligibleTracks, $playedTracks)
            ? 'artist'
            : 'title';

        if (!$allowDuplicates) {
            $this->logger->debug("No track avoids same {$unavoidableDuplicate}.");
            return null;
        }

        usort(
            $eligibleTracks,
            fn (StationPlaylistQueue $a, StationPlaylistQueue $b) =>
                ($a->last_played ?? 0) <=> ($b->last_played ?? 0)
        );

        // Pull the lowest value, which corresponds to the least recently played song.
        $validTrack = reset($eligibleTracks);

        $this->logger->warning(
            "No way to avoid same {$unavoidableDuplicate}; using least recently played song.",
            [
                'media_id' => $validTrack->media_id,
                'title' => $validTrack->title,
                'artist' => $validTrack->artist,
            ]
        );

        return $validTrack;
    }

    /**
     * Given an array of eligible tracks, return the first ID that doesn't have a duplicate artist/
     *   title with any of the previously played tracks.
     *
     * Artists are checked against $artistPlayedTracks when given, otherwise against $playedTracks.
     *
     * @param StationPlaylistQueue[] $eligibleTracks
     * @param PlayedTrack[] $playedTracks
     * @param ?PlayedTrack[] $artistPlayedTracks
     */
    public function getDistinctTrack(
        array $eligibleTracks,
        array $playedTracks,
        ?array $artistPlayedTracks = null
    ): ?StationPlaylistQueue {
        $titles = $this->getPlayedTitles($playedTracks);
        $artists = $this->getPlayedArtists($artistPlayedTracks ?? $playedTracks);

        foreach ($eligibleTracks as $track) {
            // Avoid all direct title matches.
            $title = $this->prepareStringForMatching($track->title);
            if (isset($titles[$title])) {
                continue;
            }

            // Attempt to avoid an artist match, if possible.
            $compareArtists = [];
            foreach ($this->getArtistParts($track->artist) as $compareArtist) {
                $compareArtists[$compareArtist] = $compareArtist;
            }

            if (empty(array_intersect_key($compareArtists, $artists))) {
                return $track;
            }
        }

        return null;
    }

    /**
     * @param StationPlaylistQueue[] $eligibleTracks
     * @param PlayedTrack[] $playedTracks
     */
    private function hasTrackAvoidingDuplicateTitle(array $eligibleTracks, array $playedTracks): bool
    {
        $titles = $this->getPlayedTitles($playedTracks);

        foreach ($eligibleTracks as $track) {
            if (!isset($titles[$this->prepareStringForMatching($track->title)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PlayedTrack[] $playedTracks
     *
     * @return array<string, string>
     */
    private function getPlayedTitles(array $playedTracks): array
    {
        $titles = [];
        foreach ($playedTracks as $playedTrack) {
            $title = $this->prepareStringForMatching($playedTrack['title']);
            $titles[$title] = $title;
        }

        return $titles;
    }

    /**
     * @param PlayedTrack[] $playedTracks
     *
     * @return array<string, string>
     */
    private function getPlayedArtists(array $playedTracks): array
    {
        $artists = [];
        foreach ($playedTracks as $playedTrack) {
            foreach ($this->getArtistParts($playedTrack['artist']) as $artist) {
                $artists[$artist] = $artist;
            }
        }

        return $artists;
    }

    private function getArtistParts(?string $artists): array
    {
        $dividerString = chr(7);

        $artistParts = explode(
            $dividerString,
            str_replace(self::ARTIST_SEPARATORS, $dividerString, trim($artists ?? ''))
        );

        return array_filter(
            array_map(
                [$this, 'prepareStringForMatching'],
                $artistParts
            )
        );
    }

    private function prepareStringForMatching(?string $string): string
    {
        return mb_strtolower(trim($string ?? ''));
    }
}
