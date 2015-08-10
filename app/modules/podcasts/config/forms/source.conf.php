<?php
return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements' => array(

        'type' => array('radio', array(
            'label' => 'Source Type',
            'multiOptions' => \Entity\PodcastSource::getSourceSelect(),
            'required' => true,
        )),

        'url' => array('text', array(
            'label' => 'Source URL',
            'class' => 'half-width',

            'description' => 'The address (including http[s]://) where content can be syndicated.',
            'required' => true,
        )),

        'is_active' => array('radio', array(
            'label' => 'Is Source Active',
            'multiOptions' => array(0 => 'No', 1 => 'Yes'),
            'default' => 1,

            'description' => 'Mark this source as inactive to keep it in the database but no longer pull episodes from it.',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-large',
        )),

    ),
);