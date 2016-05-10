<?php
namespace App\RadioBackend;

class LiquidSoap extends AdapterAbstract
{
    /**
     * Read configuration from external service to Station object.
     * @return bool
     */
    public function read()
    {
        /* TODO: Implement read config */
    }

    /**
     * Write configuration from Station object to the external service.
     * @return bool
     */
    public function write()
    {
        $station_base_dir = $this->station->radio_base_dir;
        $playlist_path = $station_base_dir.'/playlists';
        $media_path = $station_base_dir.'/media';
        
        $ls_config = array();

        // Clear out existing playlists directory.
        $current_playlists = array_diff(scandir($playlist_path), array('..', '.'));
        foreach($current_playlists as $list)
            @unlink($playlist_path.'/'.$list);

        // Write new playlists.
        $playlist_weights = array();
        $playlist_vars = array();

        $ls_config[] = '# Playlists';
        
        foreach($this->station->playlists as $playlist)
        {
            $playlist_file = array();

            foreach($playlist->media as $media_file)
            {
                $media_file_path = $media_path.'/'.$media_file->path;
                $playlist_file[] = $media_file_path;
            }

            $playlist_file_contents = implode("\n", $playlist_file);

            $playlist_var_name = 'playlist_'.$playlist->getShortName();
            $playlist_file_path = $playlist_path.'/'.$playlist_var_name.'.pls';

            file_put_contents($playlist_file_path, $playlist_file_contents);

            $ls_config[] = $playlist_var_name.' = playlist("'.$playlist_file_path.'")';

            $playlist_weights[] = $playlist->weight;
            $playlist_vars[] = $playlist_var_name;
        }

        $ls_config[] = '';
        $ls_config[] = '# Build Radio Station';
        $ls_config[] = 'radio = random(weights = ['.implode(', ', $playlist_weights).'],['.implode(', ', $playlist_vars).']);';
        
        // Add fallback error file.
        $error_song_path = APP_INCLUDE_ROOT.'/resources/error.mp3';

        $ls_config[] = '';
        $ls_config[] = '# Fallback Media File';
        $ls_config[] = 'security = single("'.$error_song_path.'")';
        $ls_config[] = 'radio = fallback(track_sensitive = false, [radio, security])';

        $ls_config[] = '';
        $ls_config[] = '# Outbound Broadcast';
        
        switch($this->station->frontend_type)
        {
            case 'icecast':
            default:
                if (!empty($this->station->radio_port))
                    $icecast_port = $this->station->radio_port;
                else
                    $icecast_port = 8000;

                $icecast_source_pw = $this->station->radio_source_pw;

                $output_params = [
                    '%mp3', // Required output format (%mp3 or %ogg)
                    'host = "localhost"',
                    'port = '.$icecast_port,
                    'password = "'.$icecast_source_pw.'"',
                    'mount = "radio.mp3"',
                    'radio', // Required
                ];
                $ls_config[] = 'output.icecast('.implode(', ', $output_params).')';
            break;
        }

        $ls_config_contents = implode("\n", $ls_config);
        $ls_config_path = '/etc/liquidsoap/station_'.$this->station->id.'_'.$this->station->getShortName().'.liq';

        file_put_contents($ls_config_path, $ls_config_contents);
        return true;
    }

    /**
     * Restart the executable service.
     * @return mixed
     */
    public function restart()
    {
        return exec('sudo /etc/init.d/liquidsoap force-reload');
    }
}