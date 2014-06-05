<?php
/**
 * Login Form
 */

return array(
    'method'        => 'post',
    'elements'      => array(

        'username'      => array('text', array(
            'label' => 'E-mail Address',
            'class' => 'half-width',
            'spellcheck' => 'false',
            'required' => true,
        )),

        'password'      => array('password', array(
            'label' => 'Password',
            'description' => '<a href="'.\DF\Url::route(array('module' => 'default', 'controller' => 'account', 'action' => 'forgot')).'">Forgot your password?</a>',
            'class' => 'half-width',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Log in',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);