<?php

declare(strict_types=1);

namespace App\Rss;

use MarcW\RssWriter\WriterRegistererInterface;

/**
 * Placeholder class to write the Podcast namespace for PSP-1 compliance.
 */
class PodcastNamespaceWriter implements WriterRegistererInterface
{
    public function getRegisteredWriters(): array
    {
        return [];
    }

    public function getRegisteredNamespaces(): array
    {
        return [
            'podcast' => 'https://podcastindex.org/namespace/1.0',
        ];
    }
}
