<?php
return array(
    'default' => array(
        // Home page.
        'mlpma' => array(
            'label' => 'MLP Music Archive',
            'module' => 'mlpma',
            'controller' => 'index',

            'pages' => array(
                'mlpma_test' => array(
                    'module' => 'mlpma',
                    'controller' => 'index',
                    'action' => 'test',
                    'label' => 'Test',
                ),
            ),

        ),
    ),
);
