<?php

namespace App\Entity\Api;

use Psr\Http\Message\UriInterface;

interface ResolvableUrlInterface
{
    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void;
}
