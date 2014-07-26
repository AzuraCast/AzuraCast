<?php
/**
 * Synchronization Script (Runs every 10 minutes).
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

\PVL\SyncManager::syncMedium();