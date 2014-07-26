<?php
/**
 * Synchronization Script
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

\PVL\SyncManager::syncNowplaying();