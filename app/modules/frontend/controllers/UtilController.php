<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        \PVL\Debug::setEchoMode();

        \PVL\NewsManager::syncNetwork();
    }

    /*
    public function setdatesAction()
    {
        $this->doNotRender();
        \PVL\Debug::setEchoMode();

        $db = $this->em->getConnection();

        \PVL\Debug::log('Updating song created timestamps to UNIX');

        $db->query('UPDATE songs SET created = UNIX_TIMESTAMP(created_at)');

        \PVL\Debug::log('Generating last played timestamps');

        $history_rows = $this->em->createQuery('SELECT sh FROM Entity\SongHistory sh WHERE sh.timestamp >= :threshold GROUP BY sh.song_id ORDER BY sh.timestamp DESC')
            ->setParameter('threshold', strtotime('-6 months'))
            ->getArrayResult();

        foreach($history_rows as $row)
        {
            $db->update('songs', array('last_played' => $row['timestamp']), array('id' => $row['song_id']));
        }

        \PVL\Debug::log('Done!');
    }

    public function cleanupAction()
    {
        $this->doNotRender();
        \PVL\Debug::setEchoMode();

        $external_adapters = Song::getExternalAdapters();

        $where = array();
        $where[] = 'last_played = 0';

        foreach($external_adapters as $adapter_key => $adapter_class)
        {
            $where[] = 'external_'.$adapter_key.'_id IS NULL';
        }

        $db = $this->em->getConnection();

        \PVL\Debug::log('Deleting songs that are outdated and unplayed.');

        $num_affected = $db->query('DELETE FROM songs WHERE ('.implode(' AND ', $where).')');

        \PVL\Debug::print_r($num_affected);
    }

    public function imagesAction()
    {
        $this->doNotRender();
        \PVL\Debug::setEchoMode();

        $external_adapters = Song::getExternalAdapters();

        $where = array();
        foreach($external_adapters as $adapter_key => $adapter_class)
        {
            $where[] = 's.external_'.$adapter_key.'_id IS NOT NULL';
        }

        $query = $this->em->createQuery('SELECT s FROM Entity\Song s WHERE ('.implode(' OR ', $where).')');
        $songs = $query->iterate();

        $i = 0;

        foreach($songs as $row)
        {
            $i++;

            $song = $row[0];

            $song->syncExternal(TRUE);
            $this->em->persist($song);

            if ($i % 10 == 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();

        \PVL\Debug::log('Done!');
    }
    */
}