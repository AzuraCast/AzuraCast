<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\LoggerAwareTrait;
use App\Entity\Api\StationPlaylistQueue;

final class DuplicatePrevention
{
    use LoggerAwareTrait;

    public const ARTIST_SEPARATORS = [
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
     * @param array $playedTracks
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    public function preventDuplicates(
        array $eligibleTracks = [],
        array $playedTracks = [],
        bool $allowDuplicates = false
    ): ?StationPlaylistQueue {
        if (empty($eligibleTracks)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $latestSongIdsPlayed = [];

        foreach ($playedTracks as $playedTrack) {
            $songId = $playedTrack['song_id'];

            if (!isset($latestSongIdsPlayed[$songId])) {
                $latestSongIdsPlayed[$songId] = $playedTrack['timestamp_played'];
            }
        }

        /** @var StationPlaylistQueue[] $notPlayedEligibleTracks */
        $notPlayedEligibleTracks = [];

        foreach ($eligibleTracks as $mediaId => $track) {
            $songId = $track->song_id;
            if (isset($latestSongIdsPlayed[$songId])) {
                continue;
            }

            $notPlayedEligibleTracks[$mediaId] = $track;
        }

        $validTrack = $this->getDistinctTrack($notPlayedEligibleTracks, $playedTracks);
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
        if ($allowDuplicates) {
            /** @var StationPlaylistQueue[] $mediaIdsByTimePlayed */
            $mediaIdsByTimePlayed = [];

            // For each piece of eligible media, get its latest played timestamp.
            foreach ($eligibleTracks as $track) {
                $songId = $track->song_id;
                $trackKey = $latestSongIdsPlayed[$songId] ?? 0;
                $mediaIdsByTimePlayed[$trackKey] = $track;
            }

            ksort($mediaIdsByTimePlayed);

            $validTrack = array_shift($mediaIdsByTimePlayed);

            // Pull the lowest value, which corresponds to the least recently played song.
            if (null !== $validTrack) {
                $this->logger->warning(
                    'No way to avoid same title OR same artist; using least recently played song.',
                    [
                        'media_id' => $validTrack->media_id,
                        'title' => $validTrack->title,
                        'artist' => $validTrack->artist,
                    ]
                );

                return $validTrack;
            }
        }

        return null;
    }

    /**
     * Given an array of eligible tracks, return the first ID that doesn't have a duplicate artist/
     *   title with any of the previously played tracks.
     *
     * Both should be in the form of an array, i.e.:
     *  [ 'id' => ['artist' => 'Foo', 'title' => 'Fighters'] ]
     *
     * @param StationPlaylistQueue[] $eligibleTracks
     * @param array $playedTracks
     *
     */
    public function getDistinctTrack(
        array $eligibleTracks,
        array $playedTracks
    ): ?StationPlaylistQueue {
        $artists = [];
        $titles = [];
        foreach ($playedTracks as $playedTrack) {
            $title = $this->prepareStringForMatching($playedTrack['title']);
            $titles[$title] = $title;

            foreach ($this->getArtistParts($playedTrack['artist']) as $artist) {
                $artists[$artist] = $artist;
            }
        }

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

    private function getArtistParts(string $artists): array
    {
        $dividerString = chr(7);

        $artistParts = explode(
            $dividerString,
            str_replace(self::ARTIST_SEPARATORS, $dividerString, trim($artists))
        );

        return array_filter(
            array_map(
                [$this, 'prepareStringForMatching'],
                $artistParts
            )
        );
    }

    private function prepareStringForMatching(string $string): string
    {
        return mb_strtolower(trim($string));
    }
}
