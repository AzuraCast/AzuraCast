<?php 
return array(	
	'method'		=> 'post',
    'enctype'       => 'multipart/form-data',

    'elements' => array(

        'title' => array('text', array(
            'label' => 'Song Title',
            'class' => 'half-width',
            'required' => true,
        )),

        'artist' => array('text', array(
            'label' => 'Artist Name',
            'class' => 'half-width',
            'required' => true,
        )),

        'album' => array('text', array(
            'label' => 'Album Name',
            'class' => 'half-width',
            'required' => true,
        )),

        'track_number' => array('text', array(
            'label' => 'Track Number on Album',
        )),

        'year' => array('text', array(
            'label' => 'Year of Release',
        )),

        'genre' => array('text', array(
            'label' => 'Genre(s)',
            'class' => 'half-width',
            'description' => 'Separate each genre with a comma, i.e. "Rock, Pop".',
        )),

        'art_path' => array('file', array(
            'label' => 'Replace Album Artwork',
        )),

		'submit'		=> array('submit', array(
			'type'	=> 'submit',
			'label'	=> 'Save Changes',
			'helper' => 'formButton',
			'class' => 'ui-button',
		)),

    ),
);