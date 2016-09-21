<?php
return [
    'method' => 'post',
    'elements' => [

        'title' => ['text', [
            'label' => 'Song Title',
        ]],

        'artist' => ['text', [
            'label' => 'Song Artist',
        ]],

        'album' => ['text', [
            'label' => 'Song Album',
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        ]],

    ],
];