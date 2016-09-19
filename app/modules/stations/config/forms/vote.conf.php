<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'elements' => [

        'decision' => ['radio', [
            'label' => 'Your Recommendation',
            'required' => true,
            'multiOptions' => [
                'Accept' => 'Accept the Station',
                'Decline' => 'Decline the Station',
                'Abstain' => 'Abstain / No Vote',
            ],
            'default' => 'Abstain',
        ]],

        'comments' => ['textarea', [
            'label' => 'Comments',
            'class' => 'full-width half-height',
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Submit Vote',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],

    ],
];