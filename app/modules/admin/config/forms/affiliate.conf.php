<?php 
return array(	
	'method'		=> 'post',
    'enctype'       => 'multipart/form-data',

	'elements'		=> array(

        'name' => array('text', array(
            'label' => 'Affiliate Name',
            'class' => 'half-width',
            'required' => true,
        )),

        'description' => array('textarea', array(
            'label' => 'Description',
            'class' => 'full-width half-height',
            'required' => true,
        )),

        'web_url' => array('text', array(
            'label' => 'Web URL',
            'description' => 'Include full address (with http://).',
            'class' => 'half-width',
            'required' => true,
        )),

        'image_url' => array('file', array(
            'label' => 'Upload New Icon',
            'description' => 'To replace the existing icon associated with this station, upload a new one using the file browser below. Icons should be 150x150px in dimension and preferably alpha-transparent 32-bit PNGs.',
        )),

        'is_approved' => array('radio', array(
            'label' => 'Is Active (Currently Visible)',
            'multiOptions' => array(
                0   => 'No',
                1   => 'Yes',
            ),
            'default' => 1,
            'required' => true,
        )),
        
		'submit'		=> array('submit', array(
			'type'	=> 'submit',
			'label'	=> 'Save Changes',
			'helper' => 'formButton',
			'class' => 'ui-button',
		)),
	),
);