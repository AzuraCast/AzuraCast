<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Media\RemoteAlbumArt;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;

class SongApiGenerator
{
    protected EntityManagerInterface $em;

    protected Router $router;

    protected Entity\Repository\StationRepository $stationRepo;

    protected Entity\Repository\CustomFieldRepository $customFieldRepo;

    protected RemoteAlbumArt $remoteAlbumArt;

    public function __construct(
        EntityManagerInterface $em,
        Router $router,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        RemoteAlbumArt $remoteAlbumArt
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->stationRepo = $stationRepo;
        $this->customFieldRepo = $customFieldRepo;
        $this->remoteAlbumArt = $remoteAlbumArt;
    }

    public function __invoke(
        Entity\Interfaces\SongInterface $song,
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): Entity\Api\Song {
        $response = new Entity\Api\Song();
        $response->id = $song->getSongId();
        $response->text = $song->getText() ?? '';
        $response->artist = $song->getArtist() ?? '';
        $response->title = $song->getTitle() ?? '';

        if ($song instanceof Entity\StationMedia) {
            $response->album = $song->getAlbum() ?? '';
            $response->genre = $song->getGenre() ?? '';
            $response->lyrics = $song->getLyrics() ?? '';

            $response->custom_fields = $this->getCustomFields($song->getId());
        } else {
            $response->custom_fields = $this->getCustomFields();
        }

        $response->art = $this->getAlbumArtUrl($song, $station, $baseUri, $allowRemoteArt);

        return $response;
    }

    protected function getAlbumArtUrl(
        Entity\Interfaces\SongInterface $song,
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): UriInterface {
        if (null === $baseUri) {
            $baseUri = $this->router->getBaseUrl();
        }

        if (null !== $station && $song instanceof Entity\StationMedia) {
            $mediaUpdatedTimestamp = $song->getArtUpdatedAt();

            if (0 !== $mediaUpdatedTimestamp) {
                $path = $this->router->named(
                    'api:stations:media:art',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $song->getUniqueId() . '-' . $mediaUpdatedTimestamp,
                    ]
                );

                return UriResolver::resolve($baseUri, $path);
            }
        }

        $path = ($allowRemoteArt && $this->remoteAlbumArt->enableForApis())
            ? $this->remoteAlbumArt->getUrlForSong($song)
            : null;

        if (null === $path) {
            $path = $this->stationRepo->getDefaultAlbumArtUrl($station);
        }

        return UriResolver::resolve($baseUri, Utils::uriFor($path));
    }

    /**
     * Return all custom fields, either with a null value or with the custom value assigned to the given Media ID.
     *
     * @param int|null $media_id
     *
     * @return mixed[]
     */
    protected function getCustomFields($media_id = null): array
    {
        $fields = $this->customFieldRepo->getFieldIds();

        $mediaFields = [];
        if ($media_id !== null) {
            $mediaFieldsRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT smcf.field_id, smcf.value
                    FROM App\Entity\StationMediaCustomField smcf
                    WHERE smcf.media_id = :media_id
                DQL
            )->setParameter('media_id', $media_id)
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
