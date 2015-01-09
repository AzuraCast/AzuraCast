<?php
/**
 * This file loads the necessary files and configuration to run the Doctrine CLI
 * tools.
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

require dirname(__FILE__).'/../app/bootstrap.php';

$em = $di->get('em');

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);