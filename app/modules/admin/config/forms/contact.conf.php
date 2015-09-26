<?php
return array(
    'form' => array(
        'method'        => 'post',

        'groups' => array(

            'recipients' => array(
                'legend' => 'Select Recipients',
                'elements' => array(

                    'stations' => array('multiCheckbox', array(
                        'label' => 'Stations',
                        'multiOptions' => array(),
                    )),

                    'podcasts' => array('multiCheckbox', array(
                        'label' => 'Podcasts',
                        'multiOptions' => array(),
                    )),

                ),
            ),

            'message' => array(

                'legend' => 'Compose Message',
                'elements' => array(

                    'subject' => array('text', array(
                        'label' => 'E-mail Subject',
                        'class' => 'full-width',
                        'required' => true,
                    )),

                    'body'   => array('textarea', array(
                        'label' => 'E-mail Body',
                        'id'    => 'textarea-content',
                        'class' => 'full-width full-height',
                    )),

                ),
            ),

            'submit_grp' => array(
                'elements' => array(
                    'submit_btn' => array('submit', array(
                        'type'  => 'submit',
                        'label' => 'Send Message',
                        'class' => 'ui-button',
                    )),
                ),
            ),
        ),
    ),
);