<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->any('/', 'frontend:index:index')
    ->setName('home');

$app->any('/account', 'frontend:account:index')
    ->setName('account:index');

$app->any('/login', 'frontend:account:login')
    ->setName('account:login');

$app->any('/logout', 'frontend:account:logout')
    ->setName('account:logout');

$app->any('/profile', 'frontend:profile:index')
    ->setName('profile:index');

$app->any('/profile/edit', 'frontend:profile:edit')
    ->setName('profile:edit');

$app->any('/profile/timezone', 'frontend:profile:timezone')
    ->setName('profile:timezone');