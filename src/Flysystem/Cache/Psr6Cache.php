<?php

namespace App\Flysystem\Cache;

use League\Flysystem\Cached\Storage\Psr6Cache as LeaguePsr6Cache;

class Psr6Cache extends LeaguePsr6Cache
{
    /**
     * Modification to the main cache adapter to always return null if the "has" call doesn't return true;
     * this is a "pessimistic" assumption that the cache doesn't fully manage the filesystem (which it doesn't)
     * and allows the cached adapter to take over if necessary.
     *
     * @inheritDoc
     */
    public function has($path)
    {
        $cacheHasPath = parent::has($path);
        if (true === $cacheHasPath) {
            return true;
        }

        return null;
    }
}
