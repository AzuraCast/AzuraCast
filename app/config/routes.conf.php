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

        // Info pages.
        '/about' => array(
            'module' => 'frontend',
            'controller' => 'index',
            'action' => 'about',
        ),
        '/apps' => array(
            'module' => 'frontend',
            'controller' => 'index',
            'action' => 'app',
        ),
        '/donate' => array(
            'module' => 'frontend',
            'controller' => 'index',
            'action' => 'donate',
        ),
        '/conventions' => array(
            'module' => 'frontend',
            'controller' => 'convention',
            'action' => 'index',
        ),

        // Podcasts
        '/shows' => array(
            'module' => 'frontend',
            'controller' => 'show',
            'action' => 'index',
            'name' => 'show-listing',
        ),
        '/shows/:params' => array(
            'module' => 'frontend',
            'controller' => 'show',
            'action' => 'view',
            'params' => 1,
            'name' => 'show-info',
        ),
    ),

);