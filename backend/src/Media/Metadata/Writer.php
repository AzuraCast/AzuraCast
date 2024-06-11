<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use App\Event\Media\WriteMetadata;
use JamesHeinrich\GetID3\WriteTags;
use RuntimeException;

final class Writer
{
    public function __invoke(WriteMetadata $event): void
    {
        $path = $event->getPath();

        $metadata = $event->getMetadata();
        if (null === $metadata) {
            return;
        }

        $tagwriter = new WriteTags();
        $tagwriter->filename = $path;
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $pathExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $tagFormats = match ($pathExt) {
            'mp3', 'mp2', 'mp1', 'riff' => ['id3v1', 'id3v2.3'],
            'mpc' => ['ape'],
            'flac' => ['metaflac'],
            'real' => ['real'],
            'ogg' => ['vorbiscomment'],
            default => null,
        };

        if (null === $tagFormats) {
            throw new RuntimeException('Cannot write tag formats based on file type.');
        }

        $tagwriter->tagformats = $tagFormats;

        $writeTags = $metadata->getTags();

        if ($metadata->getArtwork()) {
            $artContents = $metadata->getArtwork();
            if (false !== $artContents) {
                $writeTags['attached_picture'] = [
                    'encodingid' => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
                    'description' => 'cover art',
                    'data' => $artContents,
                    'picturetypeid' => 0x03,
                    'mime' => 'image/jpeg',
                ];
            }
        }

        // All ID3 tags have to be written as ['key' => ['value']] (i.e. with "value" at position 0).
        $tagData = [];
        foreach ($writeTags as $tagKey => $tagValue) {
            $tagData[$tagKey] = [$tagValue];
        }

        $tagwriter->tag_data = $tagData;
        $tagwriter->WriteTags();

        if (!empty($tagwriter->errors) || !empty($tagwriter->warnings)) {
            $messages = array_merge($tagwriter->errors, $tagwriter->warnings);

            throw new RuntimeException(implode(', ', $messages));
        }
    }
}
