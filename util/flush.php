<?php
/**
 * Cache flushing utility script.
 */

error_reporting(E_ERROR);
require dirname(__FILE__).'/../app/bootstrap.php';

$updated_path = DF_INCLUDE_BASE.'/.env';
@touch($updated_path);

\DF\Cache::clean();

echo 'Cache will be flushed on next page load.'."\n";
exit;