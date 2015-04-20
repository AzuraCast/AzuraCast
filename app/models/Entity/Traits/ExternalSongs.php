<?php
namespace Entity\Traits;

use Entity\Song;

use PVL\Debug;

trait ExternalSongs
{
    public static function import($new_songs, $force = false)
    {
        $db_stats = array(
            'skipped' => 0,
            'updated' => 0,
            'inserted' => 0,
            'deleted' => 0,
        );

        if (empty($new_songs))
            return false;

        Debug::startTimer('Import data into database');

        $em = self::getEntityManager();

        $existing_hashes = self::getHashes();
        $unused_hashes = $existing_hashes;

        $song_ids = Song::getIds();

        $i = 0;

        foreach($new_songs as $song_hash => $processed)
        {
            if (!in_array($song_hash, $song_ids))
                Song::getOrCreate($processed);

            if (isset($existing_hashes[$song_hash]))
            {
                if ($force)
                {
                    $db_stats['updated']++;
                    $record = self::find($processed['id']);
                }
                else
                {
                    $db_stats['skipped']++;
                    $record = null;
                }
            }
            else
            {
                $db_stats['inserted']++;
                $record = new self;
            }

            if ($record instanceof self)
            {
                $existing_ids[$processed['id']] = $processed['hash'];
                $existing_hashes[$processed['hash']] = $processed['id'];

                $record->fromArray($processed);
                $em->persist($record);
            }

            unset($unused_hashes[$song_hash]);

            $i++;
            if ($i % 200 == 0)
            {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        // Clear out any songs not found.
        $hashes_remaining = array_keys($unused_hashes);
        $db_stats['deleted'] = count($hashes_remaining);

        $em->createQuery('DELETE FROM '.__CLASS__.' e WHERE e.hash IN (:hashes)')
            ->setParameter('hashes', $hashes_remaining)
            ->execute();

        Debug::endTimer('Import data into database');

        Debug::print_r($db_stats);
        return $db_stats;
    }

    public static function match(Song $song, $force_lookup = false)
    {
        $record = self::getRepository()->findOneBy(array('hash' => $song->id));
        if ($record instanceof self)
            return $record;
        else
            return NULL;
    }

    public static function getIds()
    {
        $em = self::getEntityManager();
        $ids_raw = $em->createQuery('SELECT sebt.id, sebt.hash FROM '.__CLASS__.' sebt')->getArrayResult();

        return \DF\Utilities::ipull($ids_raw, 'hash', 'id');
    }

    public static function getHashes()
    {
        $em = self::getEntityManager();
        $ids_raw = $em->createQuery('SELECT sebt.id, sebt.hash FROM '.__CLASS__.' sebt')->getArrayResult();

        return \DF\Utilities::ipull($ids_raw, 'id', 'hash');
    }
}