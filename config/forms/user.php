<?php
/** @var array $roles */

return [
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Name'),
                'class' => 'half-width',
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-3',
            ]
        ],

        'email' => [
            'email',
            [
                'label' => __('E-mail Address'),
                'required' => true,
                'autocomplete' => 'off',
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-3',
            ]
        ],

        'auth_password' => [
            'password',
            [
                'label' => __('Reset Password'),
                'description' => __('Leave blank to use the current password.'),
                'autocomplete' => 'off',
                'required' => false,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'roles' => [
            'multiCheckbox',
            [
                'label' => __('Roles'),
                'options' => $roles,
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),

                'class' => 'btn btn-lg btn-primary',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],
    ],
];
