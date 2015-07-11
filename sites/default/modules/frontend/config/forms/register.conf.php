<?php
/**
 * Register Form
 */

$di = \Phalcon\Di::getDefault();
$config = $di->get('config');

$general_config = $config->general->toArray();
$captcha_config = $config->apis->recaptcha->toArray();

return array(
    'method' => 'post',
    'groups' => array(
        
        'account' => array(
            'legend' => 'Account Information',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Your Name',
                    'required' => true,
                )),
                
                'email' => array('text', array(
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'validators' => array('EmailAddress'),
                )),
        
                'auth_password' => array('password', array(
                    'label' => 'Password',
                    'required' => true,
                )),
                
            ),
        ),


        'captcha_grp' => array(
            'legend' => 'Spam Protection',
            'elements' => array(

                'captcha' => array('captcha', array(
                    'label' => 'Enter the code below',
                )),

            ),
        ),
        
        'submit' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Create Account and Log In',
                    'helper' => 'formButton',
                    'class' => 'ui-button',
                )),
            ),
        ),
        
    ),
);