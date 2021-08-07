<?php

declare(strict_types=1);

namespace App\MediaProcessor\Command;

use App\Entity;
use App\Utilities\Arrays;
use App\Utilities\Time;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use voku\helper\UTF8;

use const JSON_THROW_ON_ERROR;

class ReadCommand
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        string $path,
        string $jsonOutput,
        ?string $artOutput
    ): int {
        if (!is_file($path)) {
            $io->error(sprintf('File not readable: %s', $path));
            return 1;
        }

        $id3 = new \getID3();

        $id3->option_md5_data = true;
        $id3->option_md5_data_source = true;
        $id3->encoding = 'UTF-8';

        $info = $id3->analyze($path);
        $id3->CopyTagsToComments($info);

        if (!empty($info['error'])) {
            $io->error(
                sprintf(
                    'Cannot process media at path %s: %s',
                    pathinfo($path, PATHINFO_FILENAME),
                    json_encode($info['error'], JSON_THROW_ON_ERROR)
                )
            );
            return 1;
        }

        $metadata = new Entity\Metadata();

        if (is_numeric($info['playtime_seconds'])) {
            $metadata->setDuration(
                Time::displayTimeToSeconds($info['playtime_seconds']) ?? 0.0
            );
        }

        $metaTags = [];

        $toProcess = [
            $info['comments'] ?? null,
            $info['tags'] ?? null,
        ];

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

                    $metaTags[$tagName] = $this->cleanUpString((string)$tagValue);
                }
            }
        }

        $metadata->setTags($metaTags);
        $metadata->setMimeType($info['mime_type']);

        file_put_contents(
            $jsonOutput,
            json_encode($metadata, JSON_THROW_ON_ERROR),
        );

        if (null !== $artOutput) {
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
                file_put_contents(
                    $artOutput,
                    $artwork
                );
            }
        }

        return 0;
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
