<?php
/**
 * Register Form
 */

$config = Zend_Registry::get('config');
$general_config = $config->general->toArray();

return array(
    'method' => 'post',
    'groups' => array(
        
        'account_info' => array(
            'legend' => 'Account Information',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Your Name',
                    'required' => true,
                )),
                
                'email'	=> array('text', array(
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                )),
        
                'auth_password'	=> array('password', array(
                    'label' => 'Reset Password',
                    'description' => 'To change your password, enter the new password in the field below.',
                )),
                
            ),
        ),
        
        'submit' => array(
            'elements' => array(
                'submit'		=> array('submit', array(
                    'type'	=> 'submit',
                    'label'	=> 'Save Profile',
                    'helper' => 'formButton',
                    'class' => 'ui-button',
                )),
            ),
        ),
        
    ),
);