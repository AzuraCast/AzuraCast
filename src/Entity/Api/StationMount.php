<?php
namespace App\Entity\Api;

use Azura\Http\Router;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationMount")
 */
class StationMount extends StationRemote implements ResolvableUrlInterface
{
    /**
     * The relative path that corresponds to this mount point
     *
     * @OA\Property(example="/radio.mp3")
     * @var string
     */
    public $path;

    /**
     * If the mount is the default mount for the parent station
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public $is_default;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        $this->url = (string)$router->getUri($this->url, true);
    }
}
