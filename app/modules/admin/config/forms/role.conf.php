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
                'label' => _('Role Name'),
                'class' => 'half-width',
                'required' => true,
            ]],

            'actions' => ['multiCheckbox', [
                'label' => _('Actions'),
                // Supply options in controller class.
            ]],

            'submit' => ['submit', [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            ]],
        ],
    ],
];