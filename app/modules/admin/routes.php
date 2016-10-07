<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->group('/admin', function() {

    $this->get('', 'admin:index:index')
        ->setName('admin:index:index');

    $this->get('/sync/{type}', 'admin:index:sync')
        ->setName('admin:index:sync');

    $this->group('/api', function() {

        $this->get('', 'admin:api:index')
            ->setName('admin:api:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:api:edit')
            ->setName('admin:api:edit');

        $this->get('/delete/{id}', 'admin:api:delete')
            ->setName('admin:api:delete');

    });

    $this->group('/permissions', function() {

        $this->get('', 'admin:permissions:index')
            ->setName('admin:permissions:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:permissions:edit')
            ->setName('admin:permissions:edit');

        $this->get('/delete/{id}', 'admin:permissions:delete')
            ->setName('admin:permissions:delete');

        $this->get('/members/{id}', 'admin:permissions:members')
            ->setName('admin:permissions:members');

    });

    $this->map(['GET', 'POST'], '/settings', 'admin:settings:index')
        ->setName('admin:settings:index');

    $this->group('/stations', function() {

        $this->get('', 'admin:stations:index')
            ->setName('admin:stations:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:stations:edit')
            ->setName('admin:stations:edit');

        $this->get('/delete/{id}', 'admin:stations:delete')
            ->setName('admin:stations:delete');

    });

    $this->group('/users', function() {

        $this->get('', 'admin:users:index')
            ->setName('admin:users:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:users:edit')
            ->setName('admin:users:edit');

        $this->get('/delete/{id}', 'admin:users:delete')
            ->setName('admin:users:delete');

        $this->get('/login-as/{id}', 'admin:users:impersonate')
            ->setName('admin:users:impersonate');

    });

});