<?php
require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

$em = \Zend_Registry::get('em');

// Delete all current streams.
$em->createQuery('DELETE FROM Entity\StationStream')->execute();

// Get all current stations.
$stations = $em->createQuery('SELECT s FROM Entity\Station s')->execute();

foreach($stations as $station)
{
    // Create new stream record.
    $stream = new Entity\StationStream;
    $stream->station = $station;

    $stream->is_default = true;
    $stream->name = 'Main Stream';
    $stream->type = $station->type;
    $stream->stream_url = $station->stream_url;
    $stream->nowplaying_url = $station->nowplaying_url;
    $stream->nowplaying_data = $station->nowplaying_data;
    $stream->save();
}