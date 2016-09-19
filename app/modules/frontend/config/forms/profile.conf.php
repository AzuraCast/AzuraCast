<?php
/**
 * Profile Form
 */

$di = $GLOBALS['di'];
$config = $di->get('config');

$general_config = $config->general->toArray();

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => 'Account Information',
            'elements' => [

                /*
                'name' => array('text', array(
                    'label' => 'Your Name',
                    'class' => 'half-width',
                    'required' => true,
                )),
                */

                'email' => ['text', [
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'autocomplete' => 'off',
                ]],

                'auth_password' => ['password', [
                    'label' => 'Reset Password',
                    'description' => 'To change your password, enter the new password in the field below.',
                    'autocomplete' => 'off',
                ]],

            ],
        ],

        /*
        'customization_details' => array(
            'legend' => 'Site Customization',
            'elements' => array(

                'timezone' => array('select', array(
                    'label' => 'Time Zone',
                    'belongsTo' => 'customization',
                    'multiOptions' => \App\Timezone::fetchSelect(),
                    'default' => 'UTC',
                )),

            ),
        ),
        */

        'submit' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Save Profile',
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],

    ],
];