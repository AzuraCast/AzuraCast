<?php

declare(strict_types=1);

namespace App\MediaProcessor\Command;

use App\Entity\Metadata;
use App\Utilities\Json;
use getID3;
use getid3_writetags;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WriteCommand
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        string $path,
        string $jsonInput,
        ?string $artInput
    ): int {
        $getID3 = new getID3();
        $getID3->setOption(['encoding' => 'UTF8']);

        $tagwriter = new getid3_writetags();
        $tagwriter->filename = $path;

        $pathExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $tagFormats = match ($pathExt) {
            'mp3', 'mp2', 'mp1', 'riff' => ['id3v1', 'id3v2.3'],
            'mpc' => ['ape'],
            'flac' => ['metaflac'],
            'real' => ['real'],
            'ogg' => ['vorbiscomment'],
            default => null
        };

        if (null === $tagFormats) {
            $io->error('Cannot write tag formats based on file type.');
            return 1;
        }

        $tagwriter->tagformats = $tagFormats;
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $json = Json::loadFromFile($jsonInput);
        $writeTags = Metadata::fromJson($json)->getTags();

        if ($artInput && is_file($artInput)) {
            $artContents = file_get_contents($artInput);
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

            $io->error(
                sprintf(
                    'Cannot process media file %s: %s',
                    $path,
                    implode(', ', $messages)
                )
            );
            return 1;
        }

        return 0;
    }
}
