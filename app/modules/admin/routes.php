<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->group('/admin', function() {

    $this->any('', 'admin:index:index')
        ->setName('admin:index:index');

    $this->group('/api', function() {

        $this->any('', 'admin:api:index')
            ->setName('admin:api:index');

        $this->any('/edit[/{id}]', 'admin:api:edit')
            ->setName('admin:api:edit');

        $this->any('/delete/{id}', 'admin:api:delete')
            ->setName('admin:api:delete');

    });

    $this->group('/permissions', function() {

        $this->any('', 'admin:permissions:index')
            ->setName('admin:permissions:index');

        $this->any('/role/edit[/{id}]', 'admin:permissions:editrole')
            ->setName('admin:permissions:editrole');

        $this->any('/role/delete/{id}', 'admin:permissions:deleterole')
            ->setName('admin:permissions:deleterole');

        $this->any('/action/edit[/{id}]', 'admin:permissions:editaction')
            ->setName('admin:permissions:editaction');

        $this->any('/action/delete/{id}', 'admin:permissions:deleteaction')
            ->setName('admin:permissions:deleteaction');

    });

    $this->any('/settings', 'admin:settings:index')
        ->setName('admin:settings:index');

    $this->group('/stations', function() {

        $this->any('', 'admin:stations:index')
            ->setName('admin:stations:index');

        $this->any('/edit[/{id}]', 'admin:stations:edit')
            ->setName('admin:stations:edit');

        $this->any('/delete/{id}', 'admin:stations:delete')
            ->setName('admin:stations:delete');

    });

    $this->group('/users', function() {

        $this->any('', 'admin:users:index')
            ->setName('admin:users:index');

        $this->any('/edit[/{id}]', 'admin:users:edit')
            ->setName('admin:users:edit');

        $this->any('/delete/{id}', 'admin:users:delete')
            ->setName('admin:users:delete');

        $this->any('/login-as/{id}', 'admin:users:impersonate')
            ->setName('admin:users:impersonate');

    });

});