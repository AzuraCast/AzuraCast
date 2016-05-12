<?php


return array(
    'method'        => 'post',
    'elements'      => array(

        'timezone' => array('radio', array(
            'label' => 'Select New Timezone',
            'multiOptions' => \App\Timezone::fetchSelect(),
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