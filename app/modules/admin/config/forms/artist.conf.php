<?php 
return array(	
	'method'		=> 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(
        'profile' => array(
            'legend' => 'Basic Details',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Your Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'description' => array('textarea', array(
                    'label' => 'Describe Yourself',
                    'description' => 'Tell us about what you do in the pony community, what projects you\'ve worked with, or how you want to contribute in the future.',
                    'class' => 'full-width half-height',
                )),

                'types' => array('multiCheckbox', array(
                    'label' => 'Type(s) of Media',
                    'multiOptions' => \Entity\ArtistType::fetchSelect(),
                    'required' => true,
                )),

                'web_url' => array('text', array(
                    'label' => 'Personal Web Site Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                )),

                'image_url' => array('file', array(
                    'label' => 'Avatar',
                    'description' => 'This is the small image that appears on your profile. Images should be under 150x150px in size. Larger images will automatically be scaled.',
                )),

            ),
        ),

        'social' => array(
            'legend' => 'Social Networking Links',
            'description' => '
                Adding links to these services allows us to automatically update our users about your new releases and other social activity.<br>
                All fields are optional. Most of the time, your web address for these services will match the format shown in the field.
            ',

            'elements' => array(

                'rss_url' => array('text', array(
                    'label' => 'RSS Feed Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                )),

                'twitter_url' => array('text', array(
                    'label' => 'Twitter Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://www.twitter.com/YourUsername',
                )),

                'tumblr_url' => array('text', array(
                    'label' => 'Tumblr Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://YourUsername.tumblr.com',
                )),

                'facebook_url' => array('text', array(
                    'label' => 'Facebook Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://www.facebook.com/YourUserName',
                )),

                'youtube_url' => array('text', array(
                    'label' => 'YouTube Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://www.youtube.com/YourUsername',
                )),

                'soundcloud_url' => array('text', array(
                    'label' => 'SoundCloud Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://www.soundcloud.com/YourUsername',
                )),

                'deviantart_url' => array('text', array(
                    'label' => 'DeviantArt Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://YourUsername.deviantart.com',
                )),

            ),
        ),

        'sharing' => array(
            'legend' => 'Sharing Settings',
            'description' => 'Your Sharing Settings put you in control of how your content is shared across the Ponyville Live! network of stations. All of our stations seek to respect our artists\' intellectual property rights, and the settings you select here take effect across all of our stations.',

            'elements' => array(

                'license' => array('radio', array(
                    'label' => 'Permission to Broadcast Content',
                    'description' => 'Please indicate whether you would like the Ponyville Live! stations to broadcast your content as either part of their automated rotations or live shows.',
                    'multiOptions' => array(
                        'full' => 'Full - You have permission to broadcast all of my content on any Ponyville Live! station.',
                        'limited' => 'Limited - You have selective permission to broadcast some of my content on any Ponyville Live! station (specify below).',
                        'none' => 'None - You do not have permission to broadcast my content.',
                        'na' => 'N/A - Not Applicable, I do not produce broadcast-type content.',
                    ),
                )),

                'license_specifics' => array('textarea', array(
                    'label' => 'Limited Permission Details (if applicable)',
                    'description' => 'If you specified the "Limited" option above, please provide additional details here.',
                    'class' => 'half-width half-height',
                )),

                'interviews' => array('radio', array(
                    'label' => 'Available for Interviews',
                    'description' => 'From time to time, our stations interview artists in the community, in order to learn more about your interests, motivations and upcoming work. We would greatly appreciate the chance to interview you! Please indicate whether you are available for interviews below.',
                    'multiOptions' => array(
                        1 => 'Yes',
                        0 => 'No',
                    ),
                )),

                'contact_skype' => array('text', array(
                    'label' => 'Skype Username',
                    'description' => 'If you are available for interviews, please provide us with your Skype username below so that our station representatives can contact you. This information is not made available to the public.',
                    'class' => 'half-width',
                )),

                'initials' => array('text', array(
                    'label' => 'Your Initials',
                    'maxlength' => 5,
                )),

            ),
        ),

        'admin' => array(
            'legend' => 'Administrator Details',
            'elements' => array(
                'is_approved' => array('radio', array(
                    'label' => 'Is Approved',
                    'multiOptions' => array(
                        0   => 'No',
                        1   => 'Yes',
                    ),
                    'default' => 1,
                    'required' => true,
                )),
            ),
        ),

        'submit_grp' => array(
    	   'elements'		=> array(
        		'submit'		=> array('submit', array(
        			'type'	=> 'submit',
        			'label'	=> 'Save Changes',
        			'helper' => 'formButton',
        			'class' => 'ui-button',
        		)),
        	),
        ),
    ),
);