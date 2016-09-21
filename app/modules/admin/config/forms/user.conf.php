<?php
/**
 * Edit User form
 */

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'email' => ['email', [
                'label' => 'E-mail Address',
                'required' => true,
                'autocomplete' => 'off',
            ]],

            'auth_password' => ['password', [
                'label' => 'Reset Password',
                'description' => 'Leave blank to use the current password.',
                'autocomplete' => 'off',
                'required' => false,
            ]],

            'roles' => ['multiCheckbox', [
                'label' => 'Roles',
                'multiOptions' => \Entity\Role::fetchSelect(),
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