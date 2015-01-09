<?php
/**
 * Synchronization Script (Runs every hour).
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';

\PVL\SyncManager::syncLong();