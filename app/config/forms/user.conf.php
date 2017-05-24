<?php
/**
 * Edit User form
 */

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'email' => [
                'email',
                [
                    'label' => _('E-mail Address'),
                    'required' => true,
                    'autocomplete' => 'off',
                ]
            ],

            'auth_password' => [
                'password',
                [
                    'label' => _('Reset Password'),
                    'description' => _('Leave blank to use the current password.'),
                    'autocomplete' => 'off',
                    'required' => false,
                ]
            ],

            'roles' => [
                'multiCheckbox',
                [
                    'label' => _('Roles'),
                    'options' => $em->getRepository(\Entity\Role::class)->fetchSelect(),
                ]
            ],

            'submit' => [
                'submit',
                [
                    'type' => 'submit',
                    'label' => _('Save Changes'),

                    'class' => 'btn btn-lg btn-primary',
                ]
            ],
        ],
    ],
];