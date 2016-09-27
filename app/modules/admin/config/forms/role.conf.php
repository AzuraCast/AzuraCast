<?php
/**
 * Edit Role Form
 */

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'name' => ['text', [
                'label' => 'Role Name',
                'class' => 'half-width',
                'required' => true,
            ]],

            'actions' => ['multiCheckbox', [
                'label' => 'Actions',
                // Supply options in controller class.
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