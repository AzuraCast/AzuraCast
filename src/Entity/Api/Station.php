<?php
namespace App\Entity\Api;

use App\Entity;
use Azura\Http\Router;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Station")
 */
class Station implements ResolvableUrlInterface
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
     * Station "short code", used for URL and folder paths
     * @OA\Property(example="azuratest_radio")
     * @var string
     */
    public $shortcode;

    /**
     * Station description
     * @OA\Property(example="An AzuraCast station!")
     * @var string
     */
    public $description;

    /**
     * Which broadcasting software (frontend) the station uses
     * @OA\Property(example="shoutcast2")
     * @var string
     */
    public $frontend;

    /**
     * Which AutoDJ software (backend) the station uses
     * @OA\Property(example="liquidsoap")
     * @var string
     */
    public $backend;

    /**
     * The full URL to listen to the default mount of the station
     * @OA\Property(example="http://localhost:8000/radio.mp3")
     * @var string
     */
    public $listen_url;

    /**
     * If the station is public (i.e. should be shown in listings of all stations)
     * @OA\Property(example=true)
     * @var bool
     */
    public $is_public;

    /**
     * @OA\Property()
     * @var StationMount[]
     */
    public $mounts;

    /**
     * @OA\Property()
     * @var StationRemote[]
     */
    public $remotes;

    public function __clone()
    {
        $new_mounts = [];
        foreach($this->mounts as $mount) {
            $new_mounts[] = clone $mount;
        }
        $this->mounts = $new_mounts;
    }

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        $this->listen_url = (string)$router->getUri($this->listen_url, true);

        foreach($this->mounts as $mount) {
            if ($mount instanceof ResolvableUrlInterface) {
                $mount->resolveUrls($router);
            }
        }
    }
}
