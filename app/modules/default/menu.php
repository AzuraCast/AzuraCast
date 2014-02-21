<?php
return array(
    'default' => array(
        // Home page.
        'home' => array(
            'label' => 'Home',
            'module' => 'default',
            'controller' => 'index',
            'order' => -10,
            'pages' => array(),
        ),

        'artists' => array(
            'label' => 'Artists',
            'module' => 'default',
            'controller' => 'artists',
            'pages' => array(
                'artist_type' => array(
                    'module' => 'default',
                    'controller' => 'artists',
                    'action' => 'index',
                ),
                'artist_view' => array(
                    'module' => 'default',
                    'controller' => 'artists',
                    'action' => 'view',
                ),
            ),
        ),
    ),
);
