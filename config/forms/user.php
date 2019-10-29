<?php
/** @var array $roles */

return [
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Display Name'),
                'class' => 'half-width',
                'label_class' => 'mb-2',
            ],
        ],

        'email' => [
            'email',
            [
                'label' => __('E-mail Address'),
                'required' => true,
                'autocomplete' => 'new-password',
                'label_class' => 'mb-2',
                'form_group_class' => 'mt-3',
            ],
        ],

        'new_password' => [
            'password',
            [
                'label' => __('Reset Password'),
                'description' => __('Leave blank to use the current password.'),
                'autocomplete' => 'off',
                'required' => false,
                'label_class' => 'mb-2',
                'form_group_class' => 'mt-3',
            ],
        ],

        'roles' => [
            'multiCheckbox',
            [
                'label' => __('Roles'),
                'options' => $roles,
                'form_group_class' => 'mt-3',
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),

                'class' => 'btn btn-lg btn-primary',
                'form_group_class' => 'mt-3',
            ],
        ],
    ],
];
