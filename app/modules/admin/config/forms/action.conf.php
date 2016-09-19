<?php
/**
 * Add Action Form
 */

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'name' => ['text', [
                'label' => 'Action Name',
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
    ],
];