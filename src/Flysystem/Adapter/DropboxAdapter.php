<?php

namespace App\Flysystem\Adapter;

use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Spatie\Dropbox\Exceptions\BadRequest;

class DropboxAdapter extends \Spatie\FlysystemDropbox\DropboxAdapter implements AdapterInterface
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
