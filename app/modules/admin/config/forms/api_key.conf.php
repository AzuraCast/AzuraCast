<?php

return [
    'method' => 'post',
    'elements' => [

        'owner' => ['text', [
            'label' => 'API Key Owner',
            'class' => 'half-width',
            'required' => true,
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],
    ],
];