<?php
namespace App\Entity\Api\Admin;

use App\Entity;
use Azura\Http\Router;
use OpenApi\Annotations as OA;

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
    public $id;

    /**
     * Station name
     * @OA\Property(example="AzuraTest Radio")
     * @var string
     */
    public $name;

    /**
     * Station description
     * @OA\Property(example="An AzuraCast station!")
     * @var string
     */
    public $description;

    /**
     * Station homepage URL
     * @OA\Property(example="https://www.azuracast.com/")
     * @var string
     */
    public $url;

    /**
     * The genre of the station
     * @OA\Property(example="Variety")
     * @var string
     */
    public $genre;

    /**
     * Station "short code", used for URL and folder paths
     * @OA\Property(example="azuratest_radio")
     * @var string
     */
    public $shortcode;

    /**
     * Which broadcasting software (frontend) the station uses
     * @OA\Property(example="shoutcast2")
     * @var string
     */
    public $type;

    /**
     * The port used by this station to serve its broadcasts.
     * @OA\Property(example=8000)
     * @var int
     */
    public $port;

    /**
     * The relay password for the frontend (if applicable).
     * @OA\Property(example="p4ssw0rd")
     * @var string
     */
    public $password;

    /**
     * @OA\Property()
     * @var Entity\Api\StationMount[]
     */
    public $mounts;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        foreach ($this->mounts as $mount) {
            if ($mount instanceof Entity\Api\ResolvableUrlInterface) {
                $mount->resolveUrls($router);
            }
        }
    }
}
