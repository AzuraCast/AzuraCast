<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\StationMedia;
use App\Entity\StorageLocation;
use App\Media\MediaProcessor;
use App\Service\PlaylistConfiguration\Schema\MediaEntry;
use App\Utilities\File;
use FFMpeg\FFMpeg;

final class DummyMediaGenerator
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public const string DUMMY_ISRC_MARKER = 'DUMMY';

    private ?FFMpeg $ffmpeg = null;

    public function __construct(
        private readonly MediaProcessor $mediaProcessor
    ) {
    }

    public function generate(StorageLocation $storageLocation, MediaEntry $identity): ?StationMedia
    {
        $path = $identity->path;
        $length = $identity->length;

        $tmpPath = $this->renderSilentFile($length);

        try {
            $media = $this->mediaProcessor->processAndUpload($storageLocation, $path, $tmpPath);
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        if ($media === null) {
            return null;
        }

        $media->artist = $identity->artist;
        $media->title = $identity->title;
        $media->album = $identity->album;
        $media->genre = $identity->genre;
        $media->isrc = self::DUMMY_ISRC_MARKER;
        $media->length = $length;
        $media->text = null;
        $media->updateMetaFields();

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    private function renderSilentFile(float $length): string
    {
        $tmpPath = File::generateTempPath('dummy.mp3');
        @unlink($tmpPath); // Ffmpeg won't overwrite the empty file.

        $seconds = max(1, (int) round($length));

        $this->getFFMpeg()->getFFMpegDriver()->command([
            '-f',
            'lavfi',
            '-i',
            'anullsrc=r=44100:cl=stereo',
            '-t',
            (string) $seconds,
            '-q:a',
            '9',
            '-acodec',
            'libmp3lame',
            $tmpPath,
        ]);

        return $tmpPath;
    }

    private function getFFMpeg(): FFMpeg
    {
        return $this->ffmpeg ??= FFMpeg::create([], $this->logger);
    }
}
