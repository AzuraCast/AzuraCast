<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use App\Event\Media\ReadMetadata;
use App\Media\Metadata;
use App\Media\MimeType;
use App\Utilities\Arrays;
use App\Utilities\File;
use App\Utilities\Time;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Stream;
use Throwable;
use voku\helper\UTF8;

final class Reader
{
    public function __invoke(ReadMetadata $event): void
    {
        $path = $event->getPath();

        $ffprobe = FFProbe::create();
        $format = $ffprobe->format($path);

        $metadata = new Metadata();

        if (is_numeric($format->get('duration'))) {
            $metadata->setDuration(
                Time::displayTimeToSeconds($format->get('duration')) ?? 0.0
            );
        }

        $toProcess = [
            $format->get('comments'),
            $format->get('tags'),
        ];

        $metaTags = $this->aggregateMetaTags($toProcess);

        $metadata->setTags($metaTags);
        $metadata->setMimeType(MimeType::getMimeTypeFromFile($path));

        try {
            // Pull album art directly from relevant streams.
            $ffmpeg = FFMpeg::create();

            /** @var Stream[] $videoStreams */
            $videoStreams = $ffprobe->streams($path)->videos()->all();
            foreach ($videoStreams as $videoStream) {
                $streamDisposition = $videoStream->get('disposition');
                if (!isset($streamDisposition['attached_pic']) || 1 !== $streamDisposition['attached_pic']) {
                    continue;
                }

                $artOutput = File::generateTempPath('artwork.jpg');
                @unlink($artOutput); // Ffmpeg won't overwrite the empty file.

                $ffmpeg->getFFMpegDriver()->command([
                    '-i',
                    $path,
                    '-an',
                    '-vcodec',
                    'copy',
                    $artOutput,
                ]);

                $metadata->setArtwork(file_get_contents($artOutput) ?: null);
                @unlink($artOutput);
                break;
            }
        } catch (Throwable) {
            $metadata->setArtwork(null);
        }

        $event->setMetadata($metadata);
    }

    protected function aggregateMetaTags(array $toProcess): array
    {
        $metaTags = [];

        foreach ($toProcess as $tagSet) {
            if (empty($tagSet)) {
                continue;
            }

            foreach ($tagSet as $tagName => $tagContents) {
                if (!empty($tagContents) && !isset($metaTags[$tagName])) {
                    $tagValue = $tagContents;
                    if (is_array($tagValue)) {
                        // Skip pictures
                        if (isset($tagValue['data'])) {
                            continue;
                        }
                        $flatValue = Arrays::flattenArray($tagValue);
                        $tagValue = implode(', ', $flatValue);
                    }

                    $metaTags[strtolower((string)$tagName)] = $this->cleanUpString((string)$tagValue);
                }
            }
        }

        return $metaTags;
    }

    protected function cleanUpString(?string $original): string
    {
        $original ??= '';

        $string = UTF8::encode('UTF-8', $original);
        $string = UTF8::fix_simple_utf8($string);
        return UTF8::clean(
            $string,
            true,
            true,
            true,
            true,
            true
        );
    }
}
