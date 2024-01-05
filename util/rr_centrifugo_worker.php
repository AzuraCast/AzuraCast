<?php

declare(strict_types=1);

use App\AppFactory;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', '1');

require dirname(__DIR__) . '/vendor/autoload.php';

$app = AppFactory::createApp();
$di = $app->getContainer();

/** @var App\Service\Centrifugo\EventHandler $centrifugo */
$centrifugo = $di->get(App\Service\Centrifugo\EventHandler::class);

$worker = Spiral\RoadRunner\Worker::create();
$requestFactory = new RoadRunner\Centrifugo\Request\RequestFactory($worker);
$centrifugoWorker = new RoadRunner\Centrifugo\CentrifugoWorker($worker, $requestFactory);

while ($request = $centrifugoWorker->waitRequest()) {
    $response = $centrifugo->__invoke($request);
    if (null !== $response) {
        $request->respond($response);
    }
}
