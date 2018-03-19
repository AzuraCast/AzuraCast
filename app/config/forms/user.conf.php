<?php
/** @var array $roles */

return [
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Name'),
                'class' => 'half-width',
            ]
        ],

        'email' => [
            'email',
            [
                'label' => __('E-mail Address'),
                'required' => true,
                'autocomplete' => 'off',
            ]
        ],

        'auth_password' => [
            'password',
            [
                'label' => __('Reset Password'),
                'description' => __('Leave blank to use the current password.'),
                'autocomplete' => 'off',
                'required' => false,
            ]
        ],

        'roles' => [
            'multiCheckbox',
            [
                'label' => __('Roles'),
                'options' => $roles,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),

                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];