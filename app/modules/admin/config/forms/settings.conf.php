<?php
/**
 * Settings form.
 */

return array(
    /**
     * Form Configuration
     */
    'form' => array(
        'method'        => 'post',
        
        'groups'        => array(

            'flags' => array(
                'legend' => 'Site Content Display Flags',
                'elements' => array(

                    'special_event_if_stream_active' => array('radio', array(
                        'label' => 'Automatically Enable Special Events when PVL Stream Live',
                        'description' => 'This will cause the special event code below to automatically preempt the homepage rotator if any stream is active on the PVL Special Events channel.',
                        'multiOptions' => array(
                            0 => 'No',
                            1 => 'Yes',
                        ),
                        'default' => 1,
                    )),

                    'special_event' => array('radio', array(
                        'label' => 'Special Event - Manual Override',
                        'description' => 'Causes an advertisement to tune in to the stream to preempt any other rotating images on the homepage.',
                        'multiOptions' => array(
                            0 => 'No',
                            1 => 'Yes',
                        ),
                        'default' => 0,
                    )),

                    'special_event_embed_code' => array('textarea', array(
                        'label' => 'Special Event - Left Panel Embed Code',
                        'class' => 'full-width full-height input-code',
                        'spellcheck' => 'false',
                    )),

                    'special_event_chat_code' => array('textarea', array(
                        'label' => 'Special Event - Right Panel Chat',
                        'class' => 'full-width double-height input-code',
                        'spellcheck' => 'false',
                    )),

                    'special_event_station_id' => array('select', array(
                        'label' => 'Special Event - Default Station',
                        'description' => 'Configure a station to autoplay on the homepage when loaded. This should only be used when a radio station should be shown instead of the video stream (uncommon).',
                        'multiOptions' => \Entity\Station::fetchSelect(TRUE),
                    )),

                ),
            ),

            'misc' => array(
                'legend' => 'Miscellaneous Items',
                'elements' => array(

                    'notes' => array('textarea', array(
                        'label' => 'Scratch Area',
                        'description' => 'The code and other items pasted here aren\'t displayed anywhere in the system, but are useful for storing notes related to this page, including other embed codes.',
                        'class' => 'full-width double-height input-code',
                        'spellcheck' => 'false',
                    )),

                ),
            ),
            
            'submit'            => array(
                'legend'            => '',
                'elements'          => array(
                    'submit'        => array('submit', array(
                        'type'  => 'submit',
                        'label' => 'Save Changes',
                        'helper' => 'formButton',
                        'class' => 'ui-button',
                    )),
                ),
            ),
            
        ),
    ),
);