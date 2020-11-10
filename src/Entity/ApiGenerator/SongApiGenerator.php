<?php

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

class SongApiGenerator
{
    protected EntityManagerInterface $em;

    protected Router $router;

    protected Entity\Repository\StationRepository $stationRepo;

    protected Entity\Repository\CustomFieldRepository $customFieldRepo;

    public function __construct(
        EntityManagerInterface $em,
        Router $router,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\CustomFieldRepository $customFieldRepo
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->stationRepo = $stationRepo;
        $this->customFieldRepo = $customFieldRepo;
    }

    public function __invoke(
        Entity\SongInterface $song,
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null
    ): Entity\Api\Song {
        $response = new Entity\Api\Song();
        $response->id = (string)$song->getSongId();
        $response->text = (string)$song->getText();
        $response->artist = (string)$song->getArtist();
        $response->title = (string)$song->getTitle();

        if ($song instanceof Entity\StationMedia) {
            $response->album = (string)$song->getAlbum();
            $response->genre = (string)$song->getGenre();
            $response->lyrics = (string)$song->getLyrics();

            $response->art = $this->getAlbumArtUrl(
                $station,
                $song->getUniqueId(),
                $song->getArtUpdatedAt(),
                $baseUri
            );
            $response->custom_fields = $this->getCustomFields($song->getId());
        } else {
            $response->art = $this->getDefaultAlbumArtUrl($station, $baseUri);
            $response->custom_fields = $this->getCustomFields();
        }

        return $response;
    }


    protected function getAlbumArtUrl(
        ?Entity\Station $station = null,
        string $mediaUniqueId,
        int $mediaUpdatedTimestamp,
        ?UriInterface $baseUri = null
    ): UriInterface {
        if (null === $station || 0 === $mediaUpdatedTimestamp) {
            return $this->getDefaultAlbumArtUrl($station, $baseUri);
        }

        if ($baseUri === null) {
            $baseUri = $this->router->getBaseUrl();
        }

        $path = $this->router->named(
            'api:stations:media:art',
            [
                'station_id' => $station->getId(),
                'media_id' => $mediaUniqueId . '-' . $mediaUpdatedTimestamp,
            ]
        );

        return UriResolver::resolve($baseUri, $path);
    }

    protected function getDefaultAlbumArtUrl(
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null
    ): UriInterface {
        if ($baseUri === null) {
            $baseUri = $this->router->getBaseUrl();
        }

        return UriResolver::resolve($baseUri, $this->stationRepo->getDefaultAlbumArtUrl($station));
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
            $mediaFieldsRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
                smcf.field_id, smcf.value
                FROM App\Entity\StationMediaCustomField smcf
                WHERE smcf.media_id = :media_id')
                ->setParameter('media_id', $media_id)
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
