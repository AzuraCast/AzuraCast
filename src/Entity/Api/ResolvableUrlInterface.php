<?php
namespace App\Entity\Api;

use Azura\Http\Router;

interface ResolvableUrlInterface
{
    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void;
}
