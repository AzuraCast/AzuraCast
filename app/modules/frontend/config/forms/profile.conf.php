<?php
/**
 * Profile Form
 */

$di = \Phalcon\Di::getDefault();
$config = $di->get('config');

$general_config = $config->general->toArray();

return array(
    'method' => 'post',
    'groups' => array(
        
        'account_info' => array(
            'legend' => 'Account Information',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Your Name',
                    'class' => 'half-width',
                    'required' => true,
                )),
                
                'email' => array('text', array(
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'autocomplete' => 'off',
                )),
        
                'auth_password' => array('password', array(
                    'label' => 'Reset Password',
                    'description' => 'To change your password, enter the new password in the field below.',
                    'autocomplete' => 'off',
                )),
                
            ),
        ),

        'customization_details' => array(
            'legend' => 'Site Customization',
            'elements' => array(

                'theme' => array('radio', array(
                    'label' => 'Site Theme',
                    'belongsTo' => 'customization',
                    'multiOptions' => array(
                        'light'     => 'Light Theme',
                        'dark'      => 'Dark Theme',
                    ),
                    'default' => 'light',
                )),

                'timezone' => array('select', array(
                    'label' => 'Time Zone',
                    'belongsTo' => 'customization',
                    'multiOptions' => \App\Timezone::fetchSelect(),
                    'default' => 'UTC',
                )),

            ),
        ),
        
        'submit' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Save Profile',
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                )),
            ),
        ),
        
    ),
);