<?php
/**
 * Vagrant Remote API importer script.
 */

use \Entity\User;
use \Entity\Role;

require_once dirname(__FILE__) . '/../app/bootstrap.php';

set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

$config = $di->get('config');
$api_key = $config->apis->pvl_api_key;
$remote_base = 'http://ponyvillelive.com';

if (empty($api_key))
    die('No API key specified in app/config/apis.conf.php. Please contact PVL developer team!');

/**
 * Database Import
 */

echo 'Importing database entries from production server...'.PHP_EOL;

$remote_url = $remote_base.'/api/dev/import?key='.$api_key;
$local_temp = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'vagrant_import.sql';

// Pull from remote API.
$fp = fopen($local_temp, 'w');

$options = array(
    CURLOPT_FILE    => $fp,
    CURLOPT_TIMEOUT => 28800,
    CURLOPT_URL     => $remote_url,
);

$ch = curl_init();
curl_setopt_array($ch, $options);
curl_exec($ch);
fclose($fp);

// Check local file.
if (!file_exists($local_temp))
    die('File could not be saved locally.');

// If returned value was JSON error, decode it.
if (filesize($local_temp) < (1024*2))
{
    $results = file_get_contents($local_temp);
    echo $results.PHP_EOL;

    @unlink($local_temp);
    exit;
}

// Prepare and execute mysqlimport command.
$db_config = $config->db->toArray();

$destination_path = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'pvl_import.sql';

$command_flags = array(
    '-h '.$db_config['host'],
    '-u '.$db_config['user'],
    '-p'.$db_config['password'],
    $db_config['dbname']
);
$command = 'mysql '.implode(' ', $command_flags).' < '.$local_temp;

passthru($command);

@unlink($local_temp);

// Create initial user account.
$user = new User;
$user->email = 'admin@ponyvillelive.com';
$user->setAuthPassword('password');
$user->name = 'Administrator';

$role = Role::find(1);
$user->roles->add($role);
$user->save();

echo 'Database import complete.'.PHP_EOL;

/**
 * Static Asset Import
 */

echo 'Pulling static assets...'.PHP_EOL;

@mkdir(DF_INCLUDE_STATIC.DIRECTORY_SEPARATOR.'api');

$remote_url = $remote_base.'/api/dev/static?key='.$api_key;

$static_raw = file_get_contents($remote_url);
$static_result = @json_decode($static_raw, TRUE);

if (isset($static_result['result']))
{
    $static_folders = $static_result['result'];
    $ch = curl_init();

    foreach($static_folders as $dir_name => $dir_files)
    {
        $local_dir = DF_INCLUDE_STATIC.DIRECTORY_SEPARATOR.$dir_name;
        @mkdir($local_dir);

        foreach($dir_files as $file_base => $remote_url)
        {
            $local_file = $local_dir.DIRECTORY_SEPARATOR.$file_base;

            if (!file_exists($local_file))
            {
                $fp = fopen($local_file, 'w');
                $options = array(
                    CURLOPT_FILE    => $fp,
                    CURLOPT_TIMEOUT => 28800,
                    CURLOPT_URL     => $remote_url,
                );
                curl_setopt_array($ch, $options);
                curl_exec($ch);
                fclose($fp);
            }
        }

        echo ' - Finished importing "'.$dir_name.'".'.PHP_EOL;
    }
}

echo 'Static assets complete.'.PHP_EOL;
exit;