<?php

return array(
    'method'        => 'post',
    'elements'      => array(

        'owner'      => array('text', array(
            'label' => 'API Key Owner',
            'class' => 'half-width',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        )),
    ),
);