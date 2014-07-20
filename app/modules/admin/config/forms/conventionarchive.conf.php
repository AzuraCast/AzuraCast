<?php
$types = \Entity\ConventionArchive::getTypes();
$folders = \Entity\ConventionArchive::getFolders();

return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements'      => array(

        'web_url' => array('text', array(
            'label' => 'Archive Video URL',
            'class' => 'half-width',
            'required' => true,
        )),

        'type' => array('radio', array(
            'label' => 'Type of URL',
            'multiOptions' => $types,
            'required' => true,
        )),

        'folder' => array('radio', array(
            'label' => 'Archive Folder',
            'multiOptions' => $folders,
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);