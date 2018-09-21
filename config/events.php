<?php
return function (\Symfony\Component\EventDispatcher\EventDispatcher $dispatcher, \Slim\Container $di, $settings) {

    $dispatcher->addSubscriber(new \App\EventHandler\DefaultRoutes(__DIR__.'/routes.php'));
    $dispatcher->addSubscriber(new \App\EventHandler\DefaultView($di));
    $dispatcher->addSubscriber(new \App\EventHandler\DefaultNowPlaying());

    $dispatcher->addListener(\App\Event\SendWebhooks::NAME, function(\App\Event\SendWebhooks $event) use ($di) {
        /** @var \App\Webhook\Dispatcher $webhook_dispatcher */
        $webhook_dispatcher = $di[\App\Webhook\Dispatcher::class];

        $webhook_dispatcher->dispatch($event);
    });
};
