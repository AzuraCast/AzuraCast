<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Spatie\Dropbox\Exceptions\BadRequest;
use Spatie\FlysystemDropbox\DropboxAdapter as SpatieDropboxAdapter;

final class DropboxAdapter extends SpatieDropboxAdapter implements ExtendedAdapterInterface
{
    /** @inheritDoc */
    public function getMetadata(string $path): StorageAttributes
    {
        $location = $this->applyPathPrefix($path);

        try {
            $response = $this->client->getMetadata($location);
        } catch (BadRequest $e) {
            throw UnableToRetrieveMetadata::create($location, 'metadata', $e->getMessage());
        }

        return $this->normalizeResponse($response);
    }
}
