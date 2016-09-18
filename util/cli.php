<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

require dirname(__FILE__).'/../app/bootstrap.php';

$em = $di->get('em');
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
));

$cli = new \Symfony\Component\Console\Application($config->application->name.' Command Line Tools', \App\Version::getVersion());
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);

\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

$cli->addCommands(array(
    new \App\Console\Command\ClearCache($di),
    new \App\Console\Command\RestartRadio($di),
    new \App\Console\Command\Sync($di),
));

$cli->run();