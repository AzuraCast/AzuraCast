<?php
namespace App;

use App\Http\Router;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * Class ApiSupport
 * @package AzuraCast
 *
 * A dependency-injection-supported class for providing necessary data to API generator
 * functions that they can't provide from their own internal data sources.
 */
class ApiUtilities
{
    /** @var EntityManager */
    protected $em;

    /** @var Router */
    protected $router;

    /** @var Customization */
    protected $customization;

    /**
     * ApiUtilities constructor.
     * @param EntityManager $em
     * @param Router $router
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
     * @param int $station_id
     * @param string $media_unique_id
     * @param UriInterface|null $base_url
     * @return UriInterface
     */
    public function getAlbumArtUrl($station_id, $media_unique_id, UriInterface $base_url = null): UriInterface
    {
        if ($base_url === null) {
            $base_url = $this->router->getBaseUrl();
        }

        $path = $this->router->relativePathFor('api:stations:media:art', ['station' => $station_id, 'media_id' => $media_unique_id], []);
        return UriResolver::resolve($base_url, new Uri($path));
    }

    /**
     * @return UriInterface
     */
    public function getDefaultAlbumArtUrl(UriInterface $base_url = null): UriInterface
    {
        if ($base_url === null) {
            $base_url = $this->router->getBaseUrl();
        }

        return UriResolver::resolve($base_url, $this->customization->getDefaultAlbumArtUrl());
    }

    /**
     * Return all custom fields, either with a null value or with the custom value assigned to the given Media ID.
     *
     * @param null $media_id
     * @return array
     */
    public function getCustomFields($media_id = null): array
    {
        static $fields;

        if (!isset($fields)) {
            $fields = [];
            $fields_raw = $this->em->createQuery(/** @lang DQL */'SELECT 
                cf.id, cf.name, cf.short_name 
                FROM App\Entity\CustomField cf 
                ORDER BY cf.name ASC')
                ->getArrayResult();

            foreach($fields_raw as $row) {
                $fields[$row['id']] = $row['short_name'] ?? Entity\Station::getStationShortName($row['name']);
            }
        }

        $media_fields = [];
        if ($media_id !== null) {
            $media_fields_raw = $this->em->createQuery(/** @lang DQL */'SELECT 
                smcf.field_id, smcf.value 
                FROM App\Entity\StationMediaCustomField smcf 
                WHERE smcf.media_id = :media_id')
                ->setParameter('media_id', $media_id)
                ->getArrayResult();

            foreach($media_fields_raw as $row) {
                $media_fields[$row['field_id']] = $row['value'];
            }
        }

        $custom_fields = [];
        foreach($fields as $field_id => $field_key) {
            $custom_fields[$field_key] = $media_fields[$field_id] ?? NULL;
        }
        return $custom_fields;
    }
}
