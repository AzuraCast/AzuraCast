<?php

namespace App\Media\MetadataService;

use App\Entity;
use App\Event\Media\ReadMetadata;
use App\Event\Media\WriteMetadata;
use App\Exception\CannotProcessMediaException;
use App\Utilities;
use Symfony\Contracts\EventDispatcher\Event;
use voku\helper\UTF8;

class GetId3MetadataService
{
    public function __invoke(Event $event): void
    {
        if ($event instanceof ReadMetadata) {
            $metadata = $this->readMetadata($event->getPath());
            $event->setMetadata($metadata);
        } elseif ($event instanceof WriteMetadata) {
            if ($this->writeMetadata($event->getMetadata(), $event->getPath())) {
                $event->stopPropagation();
            }
        }
    }

    public function readMetadata(string $path): Entity\Metadata
    {
        $id3 = new \getID3();

        $id3->option_md5_data = true;
        $id3->option_md5_data_source = true;
        $id3->encoding = 'UTF-8';

        $info = $id3->analyze($path);

        if (!empty($info['error'])) {
            throw new CannotProcessMediaException(
                sprintf(
                    'Cannot process media file at path "%s": %s',
                    pathinfo($path, PATHINFO_FILENAME),
                    json_encode($info['error'], JSON_THROW_ON_ERROR)
                )
            );
        }

        $metadata = new Entity\Metadata();

        if (is_numeric($info['playtime_seconds'])) {
            $metadata->setDuration($info['playtime_seconds']);
        }

        if (!empty($info['tags'])) {
            $metaTags = $metadata->getTags();

            // Reverse array of tags to prefer ID3v2 tags instead of ID3v1
            $infoTags = array_reverse($info['tags']);

            foreach ($infoTags as $tagType => $tagData) {
                foreach ($tagData as $tagName => $tagContents) {
                    if (!empty($tagContents[0]) && !$metaTags->containsKey($tagName)) {
                        $tagValue = $tagContents[0];
                        if (is_array($tagValue)) {
                            $flatValue = Utilities\Arrays::flattenArray($tagValue);
                            $tagValue = implode(', ', $flatValue);
                        }

                        $metaTags->set($tagName, $this->cleanUpString($tagValue));
                    }
                }
            }
        }

        if (!empty($info['attached_picture'][0])) {
            $metadata->setArtwork($info['attached_picture'][0]['data']);
        } elseif (!empty($info['comments']['picture'][0])) {
            $metadata->setArtwork($info['comments']['picture'][0]['data']);
        } elseif (!empty($info['id3v2']['APIC'][0]['data'])) {
            $metadata->setArtwork($info['id3v2']['APIC'][0]['data']);
        } elseif (!empty($info['id3v2']['PIC'][0]['data'])) {
            $metadata->setArtwork($info['id3v2']['PIC'][0]['data']);
        }

        return $metadata;
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

    public function writeMetadata(Entity\Metadata $metadata, string $path): bool
    {
        $getID3 = new \getID3();
        $getID3->setOption(['encoding' => 'UTF8']);

        $tagwriter = new \getid3_writetags();
        $tagwriter->filename = $path;

        $tagwriter->tagformats = ['id3v1', 'id3v2.3'];
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $tags = $metadata->getTags()->toArray();

        $artwork = $metadata->getArtwork();
        if ($artwork) {
            $tags['attached_picture'] = [
                'encodingid' => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
                'description' => 'cover art',
                'data' => $artwork,
                'picturetypeid' => 0x03,
                'mime' => 'image/jpeg',
            ];
        }

        $tagData = [];
        foreach ($tags as $tagKey => $tagValue) {
            $tagData[$tagKey] = [$tagValue];
        }

        $tagwriter->tag_data = $tagData;

        $tagwriter->WriteTags();

        if (!empty($tagwriter->errors) || !empty($tagwriter->warnings)) {
            $messages = array_merge($tagwriter->errors, $tagwriter->warnings);
            throw CannotProcessMediaException::forPath(
                $path,
                implode(', ', $messages)
            );
        }

        return true;
    }
}
