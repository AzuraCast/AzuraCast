<?php

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Event\Radio\GetAlbumArt;
use App\EventDispatcher;
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

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        Router $router,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        Entity\Repository\SettingsRepository $settingsRepo,
        EventDispatcher $eventDispatcher
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->stationRepo = $stationRepo;
        $this->customFieldRepo = $customFieldRepo;
        $this->settingsRepo = $settingsRepo;
        $this->eventDispatcher = $eventDispatcher;
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

            $response->custom_fields = $this->getCustomFields($song->getId());
        } else {
            $response->custom_fields = $this->getCustomFields();
        }

        $response->art = $this->getAlbumArtUrl($song, $station, $baseUri);

        return $response;
    }

    protected function getAlbumArtUrl(
        Entity\SongInterface $song,
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null
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

        $settings = $this->settingsRepo->readSettings();

        if ($settings->getUseExternalAlbumArtInApis()) {
            $event = new GetAlbumArt($song);
            $this->eventDispatcher->dispatch($event);

            $path = $event->getAlbumArt();
        } else {
            $path = null;
        }

        if (null === $path) {
            $path = $this->stationRepo->getDefaultAlbumArtUrl($station);
        }

        return UriResolver::resolve($baseUri, $path);
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
