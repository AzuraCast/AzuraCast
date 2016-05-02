<?php
return array(

    'default_module' => 'frontend', // Use "frontend", because "default" causes namespacing problems.
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

    ),

);