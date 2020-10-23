<?php

namespace App\Media\GetId3;

use App\Media\Metadata;
use App\Media\MetadataManagerInterface;
use voku\helper\UTF8;

class GetId3MetadataManager implements MetadataManagerInterface
{
    /**
     * @inheritDoc
     */
    public function getMetadata(string $path): Metadata
    {
        $id3 = new \getID3();

        $id3->option_md5_data = true;
        $id3->option_md5_data_source = true;
        $id3->encoding = 'UTF-8';

        $info = $id3->analyze($path);

        if (!empty($info['error'])) {
            throw new \RuntimeException(sprintf(
                'Warning for uploaded media file "%s": %s',
                pathinfo($path, PATHINFO_FILENAME),
                json_encode($info['error'], JSON_THROW_ON_ERROR)
            ));
        }

        $metadata = new Metadata();

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
                        $metaTags->set($tagName, $this->cleanUpString($tagContents[0]));
                    }
                }
            }
        }

        if (!empty($info['attached_picture'][0])) {
            $metadata->setArtwork($info['attached_picture'][0]['data']);
        } elseif (!empty($info['comments']['picture'][0])) {
            $metadata->setArtwork($info['comments']['picture'][0]['data']);
        }

        return $metadata;
    }

    protected function cleanUpString(string $original): string
    {
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

    /**
     * @inheritDoc
     */
    public function writeMetadata(Metadata $metadata, string $path): bool
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
            $tags['attached_picture'][0] = [
                'encodingid' => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
                'description' => 'cover art',
                'data' => $artwork,
                'picturetypeid' => 0x03,
                'mime' => 'image/jpeg',
            ];

            $tags['comments']['picture'][0] = $tags['attached_picture'][0];
        }

        $tagwriter->tag_data = $tags;

        return $tagwriter->WriteTags();
    }
}
