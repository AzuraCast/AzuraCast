<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use App\Event\Media\ReadMetadata;
use App\Media\Enums\MetadataTags;
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
        $streams = $ffprobe->streams($path);

        $metadata = new Metadata();
        $metadata->setMimeType(MimeType::getMimeTypeFromFile($path));

        $duration = $this->getDuration($format, $streams);
        if (null !== $duration) {
            $metadata->setDuration($duration);
        }

        $metadata->setTags($this->aggregateMetaTags(
            $format,
            $streams
        ));

        $metadata->setArtwork($this->getAlbumArt(
            $streams,
            $path
        ));

        $event->setMetadata($metadata);
    }

    private function getDuration(
        FFProbe\DataMapping\Format $format,
        FFProbe\DataMapping\StreamCollection $streams
    ): ?float {
        $formatDuration = $format->get('duration');
        if (is_numeric($formatDuration)) {
            return Time::displayTimeToSeconds($formatDuration);
        }

        /** @var Stream $stream */
        foreach ($streams->audios() as $stream) {
            $duration = $stream->get('duration');
            if (is_numeric($duration)) {
                return Time::displayTimeToSeconds($duration);
            }
        }

        return null;
    }

    private function aggregateMetaTags(
        FFProbe\DataMapping\Format $format,
        FFProbe\DataMapping\StreamCollection $streams
    ): array {
        $toProcess = [
            $format->get('comments'),
            $format->get('tags'),
        ];

        /** @var Stream $stream */
        foreach ($streams->audios() as $stream) {
            $toProcess[] = $stream->get('comments');
            $toProcess[] = $stream->get('tags');
        }

        $metaTags = [];

        foreach ($toProcess as $tagSet) {
            if (empty($tagSet)) {
                continue;
            }

            foreach ($tagSet as $tagName => $tagValue) {
                if (empty($tagValue)) {
                    continue;
                }

                $tagEnum = MetadataTags::getTag((string)$tagName);
                if (null === $tagEnum) {
                    continue;
                }

                if (is_array($tagValue)) {
                    // Skip pictures
                    if (isset($tagValue['data'])) {
                        continue;
                    }
                    $flatValue = Arrays::flattenArray($tagValue);
                    $tagValue = implode(', ', $flatValue);
                }

                $tagValue = $this->cleanUpString((string)$tagValue);

                $tagName = $tagEnum->value;
                if (isset($metaTags[$tagName])) {
                    $metaTags[$tagName] .= ', ' . $tagValue;
                } else {
                    $metaTags[$tagName] = $tagValue;
                }
            }
        }

        return $metaTags;
    }

    private function getAlbumArt(
        FFProbe\DataMapping\StreamCollection $streams,
        string $path
    ): ?string {
        // Pull album art directly from relevant streams.
        $ffmpeg = FFMpeg::create();

        try {
            /** @var Stream $videoStream */
            foreach ($streams->videos() as $videoStream) {
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

                $artContent = file_get_contents($artOutput) ?: null;
                @unlink($artOutput);
                return $artContent;
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function cleanUpString(?string $original): string
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
