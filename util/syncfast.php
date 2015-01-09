<?php
/**
 * Synchronization Script
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';

\PVL\SyncManager::syncShort();