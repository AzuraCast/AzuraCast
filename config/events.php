<?php
return function (\App\EventDispatcher $dispatcher)
{

    $dispatcher->addServiceSubscriber([
        \App\EventHandler\DefaultRoutes::class,
        \App\EventHandler\DefaultView::class,
        \App\EventHandler\DefaultNowPlaying::class,
        \App\Webhook\Dispatcher::class,
    ]);

};
