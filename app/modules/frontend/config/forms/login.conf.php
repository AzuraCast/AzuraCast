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
            'class' => 'half-width',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Log in',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        )),
    ),
);