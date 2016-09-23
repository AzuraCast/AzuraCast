<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->get('/', 'frontend:index:index')
    ->setName('home');

$app->get('/account', 'frontend:account:index')
    ->setName('account:index');

$app->get('/login', 'frontend:account:login')
    ->setName('account:login');

$app->get('/logout', 'frontend:account:logout')
    ->setName('account:logout');

$app->get('/profile', 'frontend:profile:index')
    ->setName('profile:index');

$app->map(['GET', 'POST'], '/profile/edit', 'frontend:profile:edit')
    ->setName('profile:edit');

$app->map(['GET', 'POST'], '/profile/timezone', 'frontend:profile:timezone')
    ->setName('profile:timezone');

$app->group('/setup', function() {

    $this->map(['GET', 'POST'], '', 'frontend:setup:index')
        ->setName('setup:index');

    $this->map(['GET', 'POST'], '/complete', 'frontend:setup:complete')
        ->setName('setup:complete');

    $this->map(['GET', 'POST'], '/register', 'frontend:setup:register')
        ->setName('setup:register');

    $this->map(['GET', 'POST'], '/station', 'frontend:setup:station')
        ->setName('setup:station');

    $this->map(['GET', 'POST'], '/settings', 'frontend:setup:settings')
        ->setName('setup:settings');

});