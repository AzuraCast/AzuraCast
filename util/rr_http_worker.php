<?php

declare(strict_types=1);

use App\AppFactory;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', '1');

require dirname(__DIR__) . '/vendor/autoload.php';

$environment = AppFactory::buildEnvironment([
    App\Environment::IS_CLI => false,
]);
$diBuilder = AppFactory::createContainerBuilder($environment);

$httpFactory = new App\Http\HttpFactory();
$worker = \Spiral\RoadRunner\Worker::create();
$psr7Worker = new Spiral\RoadRunner\Http\PSR7Worker($worker, $httpFactory, $httpFactory, $httpFactory);

while (true) {
    try {
        $request = $psr7Worker->waitRequest();
        if ($request === null) {
            break;
        }
    } catch (\Throwable $e) {
        $psr7Worker->respond($httpFactory->createResponse(400));
        continue;
    }

    try {
        $di = AppFactory::buildContainer($diBuilder);
        $app = AppFactory::buildAppFromContainer($di);

        $response = $app->handle($request);
        $psr7Worker->respond($response);
    } catch (\Throwable $e) {
        $psr7Worker->respond($httpFactory->createResponse(500, 'Critical error'));
        $psr7Worker->getWorker()->error((string)$e);
    }

    gc_collect_cycles();
}
