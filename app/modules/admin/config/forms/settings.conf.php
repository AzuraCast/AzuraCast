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

            'system' => array(
                'legend' => 'System Settings',
                'elements' => array(

                    'base_url' => array('text', array(
                        'label' => 'Site Base URL',
                        'description' => 'The base URL where this service is located. For local testing, use "localhost". Otherwise, use either the external IP address or fully-qualified domain name pointing to the server.',
                        'default' => 'localhost',
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