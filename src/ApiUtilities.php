<?php
namespace App;

use App\Url;
use Doctrine\ORM\EntityManager;
use App\Entity;

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

    /** @var Url */
    protected $url;

    /**
     * ApiSupport constructor.
     * @param EntityManager $em
     * @param Url $url
     */
    public function __construct(EntityManager $em, Url $url)
    {
        $this->em = $em;
        $this->url = $url;
    }

    /**
     * Get the album art URL for a given unique StationMedia identifier.
     *
     * @param $station_id
     * @param $media_unique_id
     * @return string
     */
    public function getAlbumArtUrl($station_id, $media_unique_id): string
    {
        return $this->url->named('api:stations:media:art', ['station' => $station_id, 'media_id' => $media_unique_id], true);
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
            $fields_raw = $this->em->createQuery('SELECT cf.id, cf.name, cf.short_name FROM Entity\CustomField cf ORDER BY cf.name ASC')
                ->getArrayResult();

            foreach($fields_raw as $row) {
                $fields[$row['id']] = $row['short_name'] ?? Entity\Station::getStationShortName($row['name']);
            }
        }

        $media_fields = [];
        if ($media_id !== null) {
            $media_fields_raw = $this->em->createQuery('SELECT smcf.field_id, smcf.value FROM Entity\StationMediaCustomField smcf WHERE smcf.media_id = :media_id')
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
