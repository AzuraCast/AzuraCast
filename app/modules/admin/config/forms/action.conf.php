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
                'label' => _('Action Name'),
                'class' => 'half-width',
                'required' => true,
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