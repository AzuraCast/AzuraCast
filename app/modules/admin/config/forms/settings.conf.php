<?php
/**
 * Settings form.
 */

$base_url_parts = parse_url($_SERVER['HTTP_HOST']);
$base_url_default = $base_url_parts['host'];

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
                        'default' => $base_url_default,
                    )),

                    'timezone' => array('select', array(
                        'label' => 'Server Timezone',
                        'description' => 'All times displayed on the site will be based on this time zone.<br><b>Current server time is '.date('F j, Y g:ia').'.</b>',
                        'options' => \App\Timezone::fetchSelect(),
                        'default' => date_default_timezone_get(),
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
                        'class' => 'btn btn-lg btn-primary',
                    )),
                ),
            ),
            
        ),
    ),
);