<?php
return array(
    'method' => 'post',
    'groups' => array(
        
        'account' => array(
            'legend' => 'Account Information',
            'elements' => array(
                
                'email' => array('text', array(
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'validators' => array('EmailAddress'),
                )),
        
                'password' => array('password', array(
                    'label' => 'Password',
                    'required' => true,
                )),
                
            ),
        ),
        
        'submit' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Create Account',
                    'helper' => 'formButton',
                    'class' => 'ui-button',
                )),
            ),
        ),
        
    ),
);