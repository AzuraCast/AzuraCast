<?php
return array(
    'method'        => 'post',
    'elements' => array(

        'streamer_username' => array('text', array(
            'label' => 'Login Username',
            'description' => 'The streamer will use this username to connect to the radio server.',
            'required' => true,
        )),

        'streamer_password' => array('text', array(
            'label' => 'Login Password',
            'description' => 'The streamer will use this password to connect to the radio server.',
            'required' => true,
        )),

        'comments' => array('textarea', array(
            'label' => 'Account Comments',
            'description' => 'Internal notes or comments about the user, visible only on this control panel.',
        )),

        'is_active' => array('radio', array(
            'label' => 'Account is Active',
            'description' => 'Set to "Yes" to allow this account to log in and stream.',
            'required' => true,
            'default' => '1',
            'options' => array(
                0 => 'No',
                1 => 'Yes',
            ),
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        )),

    ),
);