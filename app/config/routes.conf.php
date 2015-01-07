<?php
return array(

    'default_module' => 'frontend',
    'default_controller' => 'index',
    'default_action' => 'index',

    'custom_routes' => array(
        // Homepage.
        '/' => array(
            'module' => 'frontend',
            'controller' => 'index',
            'action' => 'index',
            'name' => 'home',
        ),

        // Podcasts
        '/shows' => array(
            'module' => 'frontend',
            'controller' => 'show',
            'action' => 'index',
            'name' => 'show-listing',
        ),
        '/shows/:int' => array(
            'module' => 'frontend',
            'controller' => 'show',
            'action' => 'view',
            'id' => 1,
            'name' => 'show-info',
        ),
    ),

);