<?php
/**
 * This file loads the necessary files and configuration to run the Doctrine CLI
 * tools.
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require dirname(__FILE__).'/../app/bootstrap.php';

$application = Zend_Registry::get('application');
$application->bootstrap('doctrine');

$em = $application->getBootstrap()->getResource('doctrine');

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);