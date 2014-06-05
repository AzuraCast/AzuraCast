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

            'special' => array(
                'legend' => 'PVL Special Events Streaming Settings',
                'elements' => array(

                    'special_nowplaying_title' => array('text', array(
                        'label' => 'Now Playing Title',
                        'class' => 'half-width',
                    )),

                    'special_nowplaying_artist' => array('text', array(
                        'label' => 'Now Playing Event Name (Artist)',
                        'class' => 'half-width',
                    )),

                ),
            ),

            'flags' => array(
                'legend' => 'Site Content Display Flags',
                'elements' => array(

                    'special_event' => array('radio', array(
                        'label' => 'Special Event - Currently Streaming',
                        'description' => 'Causes an advertisement to tune in to the stream to preempt any other rotating images on the homepage.',
                        'multiOptions' => array(
                            0 => 'No',
                            1 => 'Yes',
                        ),
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
                        'description' => 'Configure a station to autoplay on the homepage when loaded. This should only be used for rare circumstances.',
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