<?php
/**
 * Vagrant Remote API importer script.
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE);

$api_key = $config->apis->pvl_api_key;

if (empty($api_key))
    die('No API key specified in app/config/apis.conf.php. Please contact PVL developer team!');

$remote_base = 'http://ponyvillelive.com';
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

echo $command;

passthru($command);

// @unlink($local_temp);

echo 'Database import complete.'.PHP_EOL;
exit;