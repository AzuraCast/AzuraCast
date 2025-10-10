<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Container\EntityManagerAwareTrait;
use App\Customization;
use App\Entity\Api\ResolvableUrl;
use App\Entity\Api\Song;
use App\Entity\Interfaces\SongInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Http\Router;
use App\Media\RemoteAlbumArt;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;

final class SongApiGenerator
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly Router $router,
        private readonly Customization $customization,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly RemoteAlbumArt $remoteAlbumArt
    ) {
    }

    public function __invoke(
        SongInterface $song,
        ?Station $station = null,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
    ): Song {
        $response = new Song();
        $response->id = $song->song_id;
        $response->text = $song->text ?? '';
        $response->artist = $song->artist ?? '';
        $response->title = $song->title ?? '';
        $response->album = $song->album ?? '';

        if ($song instanceof StationMedia) {
            $response->genre = $song->genre ?? '';
            $response->isrc = $song->isrc ?? '';
            $response->lyrics = $song->lyrics ?? '';

            $response->custom_fields = $this->getCustomFields($song->id);
        } else {
            $response->custom_fields = $this->getCustomFields();
        }

        $response->art = new ResolvableUrl(
            UriResolver::resolve(
                $baseUri ?? $this->router->getBaseUrl(),
                $this->getAlbumArtUrl($song, $station, $allowRemoteArt, $isNowPlaying)
            )
        );

        return $response;
    }

    private function getAlbumArtUrl(
        SongInterface $song,
        ?Station $station = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
    ): UriInterface {
        if (null !== $station && $song instanceof StationMedia) {
            $routeParams = [
                'station_id' => $station->short_name,
                'media_id' => $song->unique_id,
            ];

            $mediaUpdatedTimestamp = $song->art_updated_at;
            if (0 !== $mediaUpdatedTimestamp) {
                $routeParams['timestamp'] = $mediaUpdatedTimestamp;
            }

            return $this->router->namedAsUri(
                routeName: 'api:stations:media:art',
                routeParams: $routeParams
            );
        }

        if ($allowRemoteArt && $this->remoteAlbumArt->enableForApis()) {
            $url = $this->remoteAlbumArt->getUrlForSong($song);
            if (null !== $url) {
                return Utils::uriFor($url);
            }
        }

        if ($isNowPlaying && null !== $station) {
            $currentStreamer = $station->current_streamer;
            if (null !== $currentStreamer && 0 !== $currentStreamer->art_updated_at) {
                return $this->router->namedAsUri(
                    routeName: 'api:stations:streamer:art',
                    routeParams: [
                        'station_id' => $station->short_name,
                        'id' => $currentStreamer->id,
                        'timestamp' => $currentStreamer->art_updated_at,
                    ],
                );
            }
        }

        return $this->customization->getDefaultAlbumArtUrl($station);
    }

    /**
     * Return all custom fields, either with a null value or with the custom value assigned to the given Media ID.
     *
     * @param int|null $mediaId
     *
     * @return mixed[]
     */
    private function getCustomFields(?int $mediaId = null): array
    {
        $fields = $this->customFieldRepo->getFieldIds();

        $mediaFields = [];
        if ($mediaId !== null) {
            $mediaFieldsRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT IDENTITY(smcf.field) AS field_id, smcf.value
                    FROM App\Entity\StationMediaCustomField smcf
                    WHERE IDENTITY(smcf.media) = :media_id
                DQL
            )->setParameter('media_id', $mediaId)
                ->getArrayResult();

            foreach ($mediaFieldsRaw as $row) {
                $mediaFields[$row['field_id']] = $row['value'];
            }
        }

        $customFields = [];
        foreach ($fields as $fieldId => $fieldKey) {
            $customFields[$fieldKey] = $mediaFields[$fieldId] ?? null;
        }
        return $customFields;
    }
}
