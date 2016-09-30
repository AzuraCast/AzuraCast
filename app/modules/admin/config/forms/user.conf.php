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
                'label' => _('E-mail Address'),
                'required' => true,
                'autocomplete' => 'off',
            ]],

            'auth_password' => ['password', [
                'label' => _('Reset Password'),
                'description' => _('Leave blank to use the current password.'),
                'autocomplete' => 'off',
                'required' => false,
            ]],

            'roles' => ['multiCheckbox', [
                'label' => 'Roles',
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