<?php
return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'profile' => array(
            'legend' => 'Profile Information',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Station Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'genre' => array('text', array(
                    'label' => 'Station Genre',
                    'description' => 'Listed underneath the station in the player.',
                )),

                'description' => array('textarea', array(
                    'label' => 'Station Description',
                    'class' => 'full-width full-height',
                )),

                'country' => array('select', array(
                    'label' => 'Country of Broadcast',
                    'multiOptions' => \PVL\Internationalization::getCountryLookup(),
                    'default' => '',
                )),

                'image_url' => array('file', array(
                    'label' => 'Upload New Station Avatar (150x150 PNG)',
                    'description' => 'To replace the existing icon associated with this station, upload a new one using the file browser below. Icons should be 150x150px in dimension.',
                )),

                'banner_url' => array('file', array(
                    'label' => 'Upload New Promotional Banner (600x300 PNG)',
                    'description' => 'This image will be shown in the header rotator when events are promoted. Images should be 600x300.',
                )),

            ),
        ),

        'contact' => array(
            'legend' => 'Contact Information',
            'elements' => array(

                'web_url' => array('text', array(
                    'label' => 'Web URL',
                    'description' => 'Include full address (with http://).',
                    'class' => 'half-width',
                )),

                'contact_email' => array('text', array(
                    'label' => 'Contact E-mail Address',
                    'description' => 'Include to show an e-mail link for the station on the "Contact Us" page.',
                    'validators' => array('EmailAddress'),
                    'class' => 'half-width',
                )),

                'twitter_url' => array('text', array(
                    'label' => 'Twitter URL',
                    'description' => 'Include full address of the station\'s Twitter account (with http://).',
                    'class' => 'half-width',
                )),

                'facebook_url' => array('text', array(
                    'label' => 'Facebook URL',
                    'description' => 'Optional: This will be included in the "Contact Us" page if provided.',
                    'class' => 'half-width',
                )),

                'tumblr_url' => array('text', array(
                    'label' => 'Tumblr URL',
                    'description' => 'Optional: This will be included in the "Contact Us" page if provided.',
                    'class' => 'half-width',
                )),

                'gcal_url' => array('text', array(
                    'label' => 'Google Calendar XML Feed URL',
                    'description' => 'This URL can be retrieved by visiting Google Calendar, hovering over the station\'s calendar on the left sidebar, clicking the dropdown menu, then "Calendar Settings". From the settings page, click the "XML" link inside the Calendar Address area. Include full address of the feed (ending in /basic or /full) (with http://).',
                    'class' => 'half-width',
                )),

                'irc' => array('text', array(
                    'label' => 'IRC Channel Name',
                    'description' => 'Include hash tag: #channelname',
                )),

            ),
        ),

        'admin' => array(
            'legend' => 'Administrator Settings',
            'elements' => array(

                'category' => array('radio', array(
                    'label' => 'Station Category',
                    'multiOptions' => \Entity\Station::getCategorySelect(),
                    'required' => true,
                )),

                'affiliation' => array('radio', array(
                    'label' => 'PVL Affiliation Level',
                    'multiOptions' => \Entity\Station::getAffiliationSelect(),
                )),

                'is_active' => array('radio', array(
                    'label' => 'Is Active',
                    'description' => 'Is visible in the PVL network player interface.',
                    'multiOptions' => array(
                        1 => 'Yes',
                        0 => 'No',
                    ),
                    'default' => 1,
                )),

                'hide_if_inactive' => array('radio', array(
                    'label' => 'Hide if Inactive',
                    'description' => 'Remove station from the display list if it is currently offline.',
                    'multiOptions' => array(
                        1 => 'Yes',
                        0 => 'No',
                    ),
                    'default' => 0,
                )),

                'weight' => array('text', array(
                    'label' => 'Sort Order',
                    'description' => 'Lower numbers appear higher on the list of stations.',
                )),

                'requests_enabled' => array('radio', array(
                    'label' => 'Enable Request System',
                    'description' => 'Enable the "Submit Request" button under this station.',
                    'multiOptions' => array(
                        1 => 'Yes',
                        0 => 'No',
                    ),
                    'default' => 0,
                )),

                'requests_ccast_username' => array('text', array(
                    'label' => 'Request System CentovaCast Account Name',
                    'description' => 'Account username in the CentovaCast system, if requests are enabled.',
                    'class' => 'half-width',
                )),

                'requests_external_url' => array('text', array(
                    'label' => 'External URL for Third-Party Request System',
                    'description' => 'If the station is using a non-CentovaCast request system, enter the URL for it below.',
                    'class' => 'half-width',
                )),

                'admin_notes' => array('textarea', array(
                    'label' => 'Administration Notes',
                    'description' => 'These notes are only visible by network administration.',
                    'class' => 'full-width half-height',
                )),

                'station_notes' => array('textarea', array(
                    'label' => 'Station Notes',
                    'description' => 'These notes are visible/editable by station owners.',
                    'class' => 'full-width half-height',
                )),

            ),
        ),

        'submit_grp' => array(
            'elements'      => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Save Changes',
                    'helper' => 'formButton',
                    'class' => 'ui-button',
                )),
            ),
        ),
    ),
);