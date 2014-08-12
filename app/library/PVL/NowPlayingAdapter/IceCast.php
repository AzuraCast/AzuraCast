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
                    $mount[$i] = pq($cell)->html();
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
            if (count($mount) >= 10)
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
        list($artist, $track) = explode(" - ",$temp_array[9], 2);

        $np['listeners'] = (int)$temp_array[5];
        $np['artist'] = $artist;
        $np['title'] = $track;
        $np['text'] = $temp_array[9];
        $np['is_live'] = 'false';
        return $np;
    }
}