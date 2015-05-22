<?php 
return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'intro' => array(
            'legend' => 'About Artist Profiles',
            'elements' => array(
                'intro_text' => array('markup', array('markup' => '
                    <p>Thank you for submitting your Artist profile to the Ponyville Live system! Here at PVL, our growing network of radio stations, video streams and podcasts are built upon the brilliant work created by the Brony community at large, and our Artist Center is one small way that we can work directly with the artists creating this amazing content.</p>

                    <p>Artist profiles are Ponyville Live\'s way of putting you in charge of how your creative output is presented on our network, and giving you tools to work more closely with us. The Artist profile is especially useful if you are a musician or vocalist. By creating an artist profile, musicians can not only see how all of their music is performing across the network (including which songs are liked the most, played the most, etc), but you can also submit new music directly to all Ponyville Live stations from one single system.</p>

                    <p>Just so you know, we also list every artist who submits a profile on our system in our public Artists Directory, so that we can bring more exposure to your work, and direct people to your social media pages where your art originates. All of the contact information in the "Social Netowrking Links" section below is completely optional, but we encourage you to supply any items that are relevant to the work you create.</p>

                    <h3>Artist Verification</h3>
                    <p>We want to make sure that the person submitting your profile is actually you! For this reason, we require any artist profiles submitted through our system to be verified by our staff.</p>
                    <p>There are several ways to verify your profile after submitting it:</p>
                    <ul>
                        <li>Send a tweet to <a href="http://twitter.com/ponyvillelive" target="_blank">@PonyvilleLive</a> from your Twitter account,</li>
                        <li>Send an e-mail to <a href="mailto:pr@ponyvillelive.com" target="_blank">our PR team</a> with your contact information, or</li>
                        <li>Contact a member of our team on Skype or elsewhere. You can find all of our essential contact information from our <a href="'.\DF\Url::route(array('module' => 'frontend', 'controller' => 'index', 'action' => 'contact')).'" target="_blank">Contact Us page</a>.</li>
                    </ul>
                    <p>Once your profile has been approved, you will be able to access the Artist Center by logging in to the Ponyville Live! homepage.</p>
                ')),
            )

        ),

        'profile' => array(
            'legend' => 'Basic Details',
            'description' => 'This basic profile information will be shown to the public in our Artists Directory.',

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
                    'label' => 'Type(s) of Art Created',
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

                'rss_url' => array('text', array(
                    'label' => 'RSS Feed Address',
                    'description' => '<a href="http://www.whatisrss.com/" target="_blank">What is RSS?</a>',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                )),

            ),
        ),

        'sharing' => array(
            'legend' => 'Sharing Settings',
            'description' => 'Your Sharing Settings put you in control of how your content is shared across the Ponyville Live! network of stations.',

            'elements' => array(

                'about_sharing' => array('markup', array(
                    'label' => 'About Sharing Permissions',
                    'markup' => '
                        <p>The "Permission to Broadcast Content" setting below is your way of instructing the team at all Ponyville Live stations on how to broadcast or present the content you produce.</p>

                        <p>There are several important things to know about this setting:</p>

                        <dl>
                            <dt>This is not a formal or legally binding agreement.</dt>
                            <dd>We aren\'t lawyers, so we\'ll keep it simple: we make every effort to respect your preferences as an artist, but we aren\'t perfect. Much like many Brony community projects, this is a fun non-commercial hobby for us, and we don\'t want to bog it down with complex legalese. Consider this page an indication of preferences and not a legally binding document.</dd>

                            <dt>Stations operate independently from the Ponyville Live network.</dt>
                            <dd>The Ponyville Live network is built on the principle of autonomy, and as such, each member station in the network is free to control the day-to-day content of their stations. All station administrators have access to the permissions you specify here, but it is up to each of them individually to enforce those standards, as we don\'t police the entire network ourselves.</dd>

                            <dt>You can change this setting at any time.</dt>
                            <dd>Once you\'ve created an artist profile, you can log back in and edit your profile at any time. Keep in mind, though, that if you later decide to provide <i>less</i> permission to PVL, this can\'t retroactively apply to content we\'ve already played or presented. Common sense and all.</dd>

                            <dt>PVL means free promotion of your work!</dt>
                            <dd>If you aren\'t sure about whether to allow the PVL family of stations to play or present your content, just remember that our radio stations, video streams and podcasts all represent free promotion of your creative content to potentially hundreds of listeners or viewers, all of whom are looking for your kind of content already. In many cases, we even provide links from info pages to purchase the content being played, and many fans do choose to support the artists they\'re hearing or seeing on PVL.</dd>
                        </dl>
                    ',
                )),

                'license' => array('radio', array(
                    'label' => 'Permission to Broadcast Content',
                    'description' => 'Please indicate whether you would like the Ponyville Live! stations to broadcast your content as either part of their automated rotations or live shows.',
                    'multiOptions' => array(
                        'full' => 'Full - You may broadcast any of my content on any Ponyville Live! station.',
                        'limited' => 'Limited - You may broadcast some of my content on any Ponyville Live! station (specify below).',
                        'none' => 'None - Please do not broadcast my content.',
                        'na' => 'N/A - Not Applicable; I do not produce broadcast-type content.',
                    ),
                )),

                'license_specifics' => array('textarea', array(
                    'label' => 'Limited Permission Details (if applicable)',
                    'description' => 'If you specified the "Limited" option above, please provide additional details here.',
                    'class' => 'half-width half-height',
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
           'elements'       => array(
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