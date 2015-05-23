<?php
namespace Modules\Frontend\Controllers;

use Entity\ApiKey;

class DevController extends BaseController
{
    public function preDispatch()
    {
        parent::preDispatch();

        $api_key = $this->getParam('key');
        if (!ApiKey::authenticate($api_key))
            die('ERROR: API key specified is not valid.');
    }

    public function importAction()
    {
        ini_set('memory_limit', '250M');

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
            'station_streams',
            'podcast',
            'podcast_on_station',
            'podcast_episodes',
            'songs',
            'convention',
            'convention_archives',
        );

        // Compose mysqldump command.
        $db_config = $this->config->db->toArray();

        $destination_path = realpath(DF_INCLUDE_TEMP).DIRECTORY_SEPARATOR.'pvl_import.sql';

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
            $s3_path = 'db_dumps/pvlive_import.sql';
            \PVL\Service\AmazonS3::upload($destination_path, $s3_path);

            return $this->returnSuccess($s3_path);
        }
        else
        {
            return $this->returnError('MySQL Dump was not successful.');
        }
    }

    public function cleanupAction()
    {
        $s3_path = 'db_dumps/pvlive_import.sql';
        \PVL\Service\AmazonS3::delete($s3_path);

        return $this->returnSuccess('DB Import dump file deleted.');
    }
}