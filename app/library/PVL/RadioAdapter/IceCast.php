<?php
namespace PVL\RadioAdapter;

use \Entity\Station;

class IceCast extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (!$return_raw)
            return false;

        if (substr($return_raw, 0, 1) == '{')
            return $this->_processJson($return_raw, $np);
        else
            return $this->_processHtml($return_raw, $np);
    }

    protected function _processJson($return_raw, &$np)
    {
        $return = @json_decode($return_raw, true);

        if (!$return || !isset($return['icestats']['source']))
            return false;

        $sources = $return['icestats']['source'];

        if (empty($sources))
            return false;

        if (key($sources) === 0)
            $mounts = $sources;
        else
            $mounts = array($sources);

        if (count($mounts) == 0)
            return false;

        // Sort in descending order of listeners.
        usort($mounts, function($a, $b) {
            $a_list = (int)$a['listeners'];
            $b_list = (int)$b['listeners'];

            if ($a_list == $b_list)
                return 0;
            else
                return ($a_list > $b_list) ? -1 : 1;
        });

        $temp_array = $mounts[0];

        if (isset($temp_array['artist']))
        {
            $np['current_song'] = array(
                'artist' => $temp_array['artist'],
                'title' => $temp_array['title'],
                'text' => $temp_array['artist'].' - '.$temp_array['title'],
            );
        }
        else
        {
            $np['current_song'] = $this->getSongFromString($temp_array['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $temp_array['bitrate'];
        $np['meta']['format'] = $temp_array['server_type'];

        $np['listeners']['current'] = (int)$temp_array['listeners'];

        return true;
    }

    protected function _processHtml($return_raw, &$np)
    {
        // Query document for tables with stream data.
        $pq = \phpQuery::newDocument($return_raw);

        $mounts = array();

        $tables_1 = $pq->find('table:has(td.streamdata)');

        if ($tables_1->length > 0)
        {
            $tables = $tables_1;
            $table_selector = 'td.streamdata';
        }
        else
        {
            return false;
        }

        foreach($tables as $table)
        {
            $streamdata = pq($table)->find($table_selector);
            $mount = array();

            $i = 0;
            foreach($streamdata as $cell)
            {
                $pq_cell = pq($cell);

                $cell_name = $pq_cell->prev()->html();
                $cell_name_clean = preg_replace('/[^\da-z_]/i', '', str_replace(' ', '_', strtolower($cell_name)));

                $cell_value = trim($pq_cell->html());

                if (!empty($cell_name_clean) && !empty($cell_value))
                    $mount[$cell_name_clean] = $cell_value;

                $i++;
            }

            $mounts[] = $mount;
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

        $np['current_song'] = $this->getSongFromString($temp_array['current_song'], ' - ');

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $temp_array['bitrate'];
        $np['meta']['format'] = $temp_array['content_type'];

        $np['listeners']['current'] = (int)$temp_array['current_listeners'];

        return true;
    }
}