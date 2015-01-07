<?php
/**
 * Synchronization Script
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';

$options = getopt('', array('segment::'));
$segment = (!empty($options['segment'])) ? $options['segment'] : 1;

define('NOWPLAYING_SEGMENT', $segment);

\PVL\SyncManager::syncNowplaying();