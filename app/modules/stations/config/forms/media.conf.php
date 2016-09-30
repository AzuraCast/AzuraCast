<?php
return [
    'method' => 'post',
    'elements' => [

        'title' => ['text', [
            'label' => _('Song Title'),
        ]],

        'artist' => ['text', [
            'label' => _('Song Artist'),
        ]],

        'album' => ['text', [
            'label' => _('Song Album'),
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => _('Save Changes'),
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        ]],

    ],
];