<?php

namespace App\Entity\Api\Admin;

use App\Entity;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_Admin_Relay")
 */
class Relay implements Entity\Api\ResolvableUrlInterface
{
    /**
     * Station ID
     * @OA\Property(example=1)
     * @var int
     */
    public int $id;

    /**
     * Station name
     * @OA\Property(example="AzuraTest Radio")
     * @var string
     */
    public string $name;

    /**
     * Station description
     * @OA\Property(example="An AzuraCast station!")
     * @var string
     */
    public string $description;

    /**
     * Station homepage URL
     * @OA\Property(example="https://www.azuracast.com/")
     * @var string
     */
    public string $url;

    /**
     * The genre of the station
     * @OA\Property(example="Variety")
     * @var string
     */
    public string $genre;

    /**
     * Station "short code", used for URL and folder paths
     * @OA\Property(example="azuratest_radio")
     * @var string
     */
    public string $shortcode;

    /**
     * Which broadcasting software (frontend) the station uses
     * @OA\Property(example="shoutcast2")
     * @var string
     */
    public string $type;

    /**
     * The port used by this station to serve its broadcasts.
     * @OA\Property(example=8000)
     * @var int
     */
    public int $port;

    /**
     * The relay password for the frontend (if applicable).
     * @OA\Property(example="p4ssw0rd")
     * @var string
     */
    public string $relay_pw;

    /**
     * The administrator password for the frontend (if applicable).
     * @OA\Property(example="p4ssw0rd")
     * @var string
     */
    public string $admin_pw;

    /**
     * @OA\Property()
     * @var Entity\Api\StationMount[]
     */
    public array $mounts = [];

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        foreach ($this->mounts as $mount) {
            if ($mount instanceof Entity\Api\ResolvableUrlInterface) {
                $mount->resolveUrls($base);
            }
        }
    }
}
