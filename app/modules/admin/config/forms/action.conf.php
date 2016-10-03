<?php
/**
 * Add Action Form
 */

return [
    'method' => 'post',
    'elements' => [

        'name' => ['text', [
            'label' => _('Action Name'),
            'class' => 'half-width',
            'required' => true,
        ]],

        'is_global' => ['radio', [
            'label' => _('System-Wide or Per-Station'),
            'options' => [
                1 => _('System-Wide'),
                0 => _('Per-Station'),
            ],
            'default' => 1,
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => _('Save Changes'),
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],
    ],
];