<?php
$coverage_options_raw = \Entity\Convention::getCoverageLevels();
$coverage_options = array();

foreach($coverage_options_raw as $c_key => $c_opt)
{
    $coverage_options[$c_key] = '&nbsp;<i class="'.$c_opt['icon'].'"></i> '.$c_opt['text'];
}

return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements'      => array(

        'name' => array('text', array(
            'label' => 'Convention Name',
            'class' => 'half-width',
            'required' => true,
        )),

        'location' => array('text', array(
            'label' => 'Convention Location',
            'class' => 'half-width',
            'required' => true,
        )),

        'coverage_level' => array('radio', array(
            'label' => 'PVL Coverage Level',
            'multiOptions' => $coverage_options,
            'escape' => false,
            'default' => 'full',
            'required' => true,
        )),

        'start_date' => array('unixDate', array(
            'label' => 'Start Date',
        )),

        'end_date' => array('unixDate', array(
            'label' => 'End Date',
        )),

        'web_url' => array('text', array(
            'label' => 'Homepage URL',
            'class' => 'half-width',
        )),

        'image_url' => array('file', array(
            'label' => 'Convention Image',
            'description' => 'Use the same size image as the main PVL banner rotator (1150x200). PNG preferred.',
        )),

        'schedule_url' => array('text', array(
            'label' => 'Schedule URL',
            'class' => 'half-width',
        )),

        'signup_enabled' => array('radio', array(
            'label' => 'Signup Enabled?',
            'description' => 'Enable the convention signup form for camera operators and other staff.',
            'multiOptions' => array(0 => 'No', 1 => 'Yes'),
            'default' => 1,
            'required' => true,
        )),

        'signup_notes' => array('textarea', array(
            'label' => 'Special Signup Notes',
            'description' => 'If there are special considerations for this convention, include them here and they will appear in the signup form.',
            'class' => 'full-width half-height',
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);