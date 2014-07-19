<?php
class Api_DevController extends \PVL\Controller\Action\Api
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->hasParam('key'))
            die('ERROR: No API key specified!');

        $supplied_key = $this->getParam('key');
        $accepted_api_keys = $this->config->apis->pvl_api_keys->toArray();

        $key_accepted = false;
        foreach($accepted_api_keys as $api_key)
        {
            if (strcmp(md5($api_key), $supplied_key) === 0)
                $key_accepted = true;
        }

        if (!$key_accepted)
            die('ERROR: API key specified is not valid.');
    }

    public function importAction()
    {
        ini_set('memory_limit', '250M');
        ini_set('display_errors', 0);

        // Tables to export from local DB.
        $tables = array(
            'settings',
            'block',
            'affiliates',
            'rotators',
            'action',
            'role',
            'role_has_action',
            'station',
            'podcast',
            'podcast_on_station',
            'songs',
        );

        // Compose mysqldump command.
        $db_config = $this->config->db->toArray();

        $destination_path = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'pvl_import.sql';

        $command_flags = array(
            '-h '.$db_config['host'],
            '-u '.$db_config['user'],
            '-p'.$db_config['password'],
            '--no-create-info',
            '--skip-extended-insert',
            '--complete-insert',
            '--single-transaction',
            '--quick',
            $db_config['dbname'],
            implode(' ', $tables),
        );
        $command = 'mysqldump '.implode(' ', $command_flags).' > '.$destination_path;

        // Execute mysqldump.
        exec($command);

        // Stream file out to screen.
        if (file_exists($destination_path))
        {
            $fp = fopen($destination_path, 'r');

            header("Content-Type: application/octet-stream");
            header("Content-Length: " . filesize($destination_path));

            fpassthru($fp);
            fclose($fp);

            @unlink($destination_path);
        }
    }

    public function staticAction()
    {
        $directories = array(
            'affiliates',
            'artists',
            'podcasts',
            'rotators',
            'songs',
            'stations',
            'conventions',
        );

        $static_files = array();

        foreach($directories as $dir)
        {
            $dir_path = DF_INCLUDE_STATIC.DIRECTORY_SEPARATOR.$dir;
            $files_raw = @scandir($dir_path);

            foreach($files_raw as $file)
            {
                $path = $dir_path.DIRECTORY_SEPARATOR.$file;
                if (!is_dir($path))
                    $static_files[$dir][$file] = \DF\Url::content($dir.'/'.$file);
            }
        }

        $this->returnSuccess($static_files);
    }
}