<?php
namespace App;

use App\Http\Router;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * A dependency-injection-supported class for providing necessary data to API generator
 * functions that they can't provide from their own internal data sources.
 */
class ApiUtilities
{
    /** @var EntityManager */
    protected EntityManager $em;

    /** @var Router */
    protected Router $router;

    /** @var Customization */
    protected Customization $customization;

    /**
     * @param EntityManager $em
     * @param Router $router
     * @param Customization $customization
     */
    public function __construct(EntityManager $em, Router $router, Customization $customization)
    {
        $this->em = $em;
        $this->router = $router;
        $this->customization = $customization;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the album art URL for a given unique StationMedia identifier.
     *
     * @param int $stationId
     * @param string $mediaUniqueId
     * @param int $mediaUpdatedTimestamp
     * @param UriInterface|null $baseUri
     *
     * @return UriInterface
     */
    public function getAlbumArtUrl(
        $stationId,
        $mediaUniqueId,
        int $mediaUpdatedTimestamp,
        UriInterface $baseUri = null
    ): UriInterface {
        if (0 === $mediaUpdatedTimestamp) {
            return $this->getDefaultAlbumArtUrl($baseUri);
        }

        if ($baseUri === null) {
            $baseUri = $this->router->getBaseUrl();
        }

        $path = $this->router->named(
            'api:stations:media:art',
            [
                'station_id' => $stationId,
                'media_id' => $mediaUniqueId . '-' . $mediaUpdatedTimestamp,
            ]
        );
        return UriResolver::resolve($baseUri, $path);
    }

    /**
     * @param UriInterface|null $baseUri
     *
     * @return UriInterface
     */
    public function getDefaultAlbumArtUrl(UriInterface $baseUri = null): UriInterface
    {
        if ($baseUri === null) {
            $baseUri = $this->router->getBaseUrl();
        }

        return UriResolver::resolve($baseUri, $this->customization->getDefaultAlbumArtUrl());
    }

    /**
     * Return all custom fields, either with a null value or with the custom value assigned to the given Media ID.
     *
     * @param null $media_id
     *
     * @return array
     */
    public function getCustomFields($media_id = null): array
    {
        static $fields;

        if (!isset($fields)) {
            $fields = [];
            $fields_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT
                cf.id, cf.name, cf.short_name
                FROM App\Entity\CustomField cf
                ORDER BY cf.name ASC')
                ->getArrayResult();

            foreach ($fields_raw as $row) {
                $fields[$row['id']] = $row['short_name'] ?? Entity\Station::getStationShortName($row['name']);
            }
        }

        $media_fields = [];
        if ($media_id !== null) {
            $media_fields_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT
                smcf.field_id, smcf.value
                FROM App\Entity\StationMediaCustomField smcf
                WHERE smcf.media_id = :media_id')
                ->setParameter('media_id', $media_id)
                ->getArrayResult();

            foreach ($media_fields_raw as $row) {
                $media_fields[$row['field_id']] = $row['value'];
            }
        }

        $custom_fields = [];
        foreach ($fields as $field_id => $field_key) {
            $custom_fields[$field_key] = $media_fields[$field_id] ?? null;
        }
        return $custom_fields;
    }
}
