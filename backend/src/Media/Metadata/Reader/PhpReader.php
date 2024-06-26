<?php

declare(strict_types=1);

namespace App\Media\Metadata\Reader;

use App\Container\LoggerAwareTrait;
use App\Event\Media\ReadMetadata;
use App\Media\Metadata;
use App\Utilities\Time;
use JamesHeinrich\GetID3\GetID3;
use RuntimeException;
use Throwable;

use const JSON_THROW_ON_ERROR;

final class PhpReader extends AbstractReader
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

            // ID3v2 should always supersede ID3v1.
            if (isset($info['tags']['id3v2'])) {
                unset($info['tags']['id3v1']);
            }

            if (!empty($info['tags'])) {
                $toProcess = $info['tags'];
            } else {
                $toProcess = [
                    $info['comments'] ?? [],
                ];
            }

            $toProcess[] = $this->convertReplayGainBackIntoText($info['replay_gain'] ?? []);

            $this->aggregateMetaTags($metadata, $toProcess);

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

    protected function convertReplayGainBackIntoText(array $row): array
    {
        $return = [];

        if (isset($row['track']['peak'])) {
            $return['replaygain_track_peak'] = $row['track']['peak'];
        }
        if (isset($row['track']['originator'])) {
            $return['replaygain_track_originator'] = $row['track']['originator'];
        }
        if (isset($row['track']['adjustment'])) {
            $return['replaygain_track_gain'] = $row['track']['adjustment'] . ' dB';
        }
        if (isset($row['album']['peak'])) {
            $return['replaygain_album_peak'] = $row['album']['peak'];
        }
        if (isset($row['album']['originator'])) {
            $return['replaygain_album_originator'] = $row['album']['originator'];
        }
        if (isset($row['album']['adjustment'])) {
            $return['replaygain_album_gain'] = $row['album']['adjustment'] . ' dB';
        }

        if (isset($row['reference_volume'])) {
            $return['replaygain_reference_loudness'] = $row['reference_volume'] . ' LUFS';
        }

        return $return;
    }
}
