<?php

declare(strict_types=1);

namespace App\Media\Metadata\Reader;

use App\Container\LoggerAwareTrait;
use App\Event\Media\ReadMetadata;
use App\Media\Metadata;
use App\Utilities\Arrays;
use App\Utilities\Strings;
use App\Utilities\Time;
use JamesHeinrich\GetID3\GetID3;

use const JSON_THROW_ON_ERROR;

use RuntimeException;
use Throwable;

final class PhpReader
{
    use LoggerAwareTrait;

    public function __invoke(ReadMetadata $event): void
    {
        $path = $event->getPath();

        try {
            $getid3 = new GetID3();
            $getid3->option_md5_data = true;
            $getid3->option_md5_data_source = true;
            $getid3->encoding = 'UTF-8';

            $info = $getid3->analyze($path);
            $getid3->CopyTagsToComments($info);

            if (!empty($info['error'])) {
                throw new RuntimeException(
                    json_encode($info['error'], JSON_THROW_ON_ERROR)
                );
            }

            $metadata = new Metadata();

            if (is_numeric($info['playtime_seconds'])) {
                $metadata->setDuration(
                    Time::displayTimeToSeconds($info['playtime_seconds']) ?? 0.0
                );
            }

            $toProcess = [
                $info['comments'] ?? null,
                $info['tags'] ?? null,
            ];

            $metaTags = $this->aggregateMetaTags($toProcess);

            $metadata->setTags($metaTags);
            $metadata->setMimeType($info['mime_type']);

            $artwork = null;
            if (!empty($info['attached_picture'][0])) {
                $artwork = $info['attached_picture'][0]['data'];
            } elseif (!empty($info['comments']['picture'][0])) {
                $artwork = $info['comments']['picture'][0]['data'];
            } elseif (!empty($info['id3v2']['APIC'][0]['data'])) {
                $artwork = $info['id3v2']['APIC'][0]['data'];
            } elseif (!empty($info['id3v2']['PIC'][0]['data'])) {
                $artwork = $info['id3v2']['PIC'][0]['data'];
            }

            if (!empty($artwork)) {
                $metadata->setArtwork($artwork);
            }

            $event->setMetadata($metadata);
            $event->stopPropagation();
        } catch (Throwable $e) {
            $this->logger->info(
                sprintf(
                    'getid3 failed for file %s: %s',
                    $path,
                    $e->getMessage()
                ),
                [
                    'exception' => $e,
                ]
            );
        }
    }

    private function aggregateMetaTags(array $toProcess): array
    {
        $metaTags = [];

        foreach ($toProcess as $tagSet) {
            if (empty($tagSet)) {
                continue;
            }

            foreach ($tagSet as $tagName => $tagContents) {
                if (!empty($tagContents[0]) && !isset($metaTags[$tagName])) {
                    $tagValue = $tagContents[0];
                    if (is_array($tagValue)) {
                        // Skip pictures
                        if (isset($tagValue['data'])) {
                            continue;
                        }
                        $flatValue = Arrays::flattenArray($tagValue);
                        $tagValue = implode(', ', $flatValue);
                    }

                    $metaTags[(string)$tagName] = Strings::stringToUtf8((string)$tagValue);
                }
            }
        }

        return $metaTags;
    }
}
