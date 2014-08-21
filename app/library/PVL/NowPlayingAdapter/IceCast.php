<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class IceCast extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl();

        if (!$return_raw)
            return false;

        // Query document for tables with stream data.
        $pq = \phpQuery::newDocument($return_raw);

        $tables = $pq->find('table:has(td.streamdata)');
        $mounts = array();

        if ($tables->length > 0)
        {
            foreach($tables as $table)
            {
                $streamdata = pq($table)->find('td.streamdata');
                $mount = array();

                $i = 0;
                foreach($streamdata as $cell)
                {
                    $pq_cell = pq($cell);

                    $cell_name = $pq_cell->prev()->html();
                    $cell_name_clean = preg_replace('/[^\da-z_]/i', '', str_replace(' ', '_', strtolower($cell_name)));

                    $mount[$cell_name_clean] = $pq_cell->html();
                    $i++;
                }

                $mounts[] = $mount;
            }
        }

        if (count($mounts) == 0)
            return false;

        $active_mounts = array();
        foreach($mounts as $mount)
        {
            if (count($mount) >= 9)
                $active_mounts[] = $mount;
        }

        if (count($active_mounts) == 0)
            return false;

        // Sort in descending order of listeners.
        usort($active_mounts, function($a, $b) {
            $a_list = (int)$a[5];
            $b_list = (int)$b[5];

            if ($a_list == $b_list)
                return 0;
            else
                return ($a_list > $b_list) ? -1 : 1;
        });

        $temp_array = $active_mounts[0];
        list($artist, $track) = explode(" - ", $temp_array['current_song'], 2);

        $np['listeners'] = (int)$temp_array['current_listeners'];
        $np['artist'] = $artist;
        $np['title'] = $track;
        $np['text'] = $temp_array['current_song'];
        $np['is_live'] = 'false';
        return $np;
    }
}