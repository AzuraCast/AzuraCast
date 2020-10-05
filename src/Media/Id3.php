<?php
namespace App\Media;

use getID3;
use getid3_writetags;
use RuntimeException;

class Id3
{
    public static function read(
        string $path
    ): array {
        $id3 = new getID3;

        $id3->option_md5_data = true;
        $id3->option_md5_data_source = true;
        $id3->encoding = 'UTF-8';

        $info = $id3->analyze($path);

        if (!empty($info['error'])) {
            throw new RuntimeException(sprintf(
                'Warning for uploaded media file "%s": %s',
                pathinfo($path, PATHINFO_FILENAME),
                json_encode($info['error'], JSON_THROW_ON_ERROR)
            ));
        }

        return $info;
    }

    public static function write(
        string $path,
        array $tags
    ): bool {
        $getID3 = new getID3;
        $getID3->setOption(['encoding' => 'UTF8']);

        $tagwriter = new getid3_writetags;
        $tagwriter->filename = $path;

        $tagwriter->tagformats = ['id3v1', 'id3v2.3'];
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $tagwriter->tag_data = $tags;

        return $tagwriter->WriteTags();
    }
}