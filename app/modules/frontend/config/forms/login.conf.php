<?php
/**
 * Login Form
 */

return [
    'method' => 'post',
    'elements' => [

        'username' => ['text', [
            'label' => 'E-mail Address',
            'class' => 'half-width',
            'spellcheck' => 'false',
            'required' => true,
        ]],

        'password' => ['password', [
            'label' => 'Password',
            'class' => 'half-width',
            'required' => true,
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Log in',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],
    ],
];