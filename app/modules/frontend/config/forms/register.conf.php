<?php
/**
 * Register Form
 */

$config = Zend_Registry::get('config');
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
                    'captcha' => 'ReCaptcha',
                    'captchaOptions' => array(
                        'captcha' => 'ReCaptcha',
                        'service' => new \Zend_Service_ReCaptcha(
                            $captcha_config['public_key'],
                            $captcha_config['private_key'],
                            array('ssl' => DF_IS_SECURE)
                        ),
                    ),
                    'helper' => 'formLabel',
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