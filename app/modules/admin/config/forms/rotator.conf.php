<?php 
return array(	
	'method'		=> 'post',
    'enctype'       => 'multipart/form-data',

	'elements'		=> array(

        'image_url' => array('file', array(
            'label' => 'Upload New Icon',
            'description' => 'To replace the existing icon associated with this station, upload a new one using the file browser below. Icons should be 150x150px in dimension.',
        )),

        'web_url' => array('text', array(
            'label' => 'Web URL',
            'description' => 'Include full address (with http://).',
            'class' => 'half-width',
        )),

        'description' => array('textarea', array(
            'label' => 'Description',
            'class' => 'full-width half-height',
        )),
        
		'submit'		=> array('submit', array(
			'type'	=> 'submit',
			'label'	=> 'Save Changes',
			'helper' => 'formButton',
			'class' => 'ui-button',
		)),
	),
);