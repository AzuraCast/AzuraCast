<?php 
return array(	
	'method'		=> 'post',
    'enctype'       => 'multipart/form-data',

    'elements' => array(

        'decision' => array('radio', array(
            'label' => 'Your Recommendation',
            'required' => true,
            'multiOptions' => array(
                'Accept' => 'Accept the Station',
                'Decline' => 'Decline the Station',
                'Abstain' => 'Abstain / No Vote',
            ),
            'default' => 'Abstain',
        )),

        'comments' => array('textarea', array(
            'label' => 'Comments',
            'class' => 'full-width half-height',
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Submit Vote',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),

    ),
);