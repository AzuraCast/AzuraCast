<?php 
return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'about' => array(
            'legend' => 'About Ponyville Live! Syndicated Shows &amp; Podcasts',
            'elements' => array(

                'about_text' => array('markup', array(
                    'markup' => '
                        <p>We are honored that your show is interested in being syndicated on the Ponyville Live! (or, as we call it, PVL) platform. For over two years, PVL has delivered countless hours of live programming, 24/7 radio stations, video streams, convention coverage, and more to Bronies across the world, along with some of the community\'s finest podcasts and shows, which have seen tens of thousands of extra viewers from our web site, apps and plugins.</p>

                        <p><b>Station Expectations:</b> All shows/podcasts listed on PVL are syndicated on the web site without any requirements for branding or partnership with the PVL network. Some stations listed on PVL work closely with the network to cover conventions and plan large events, but this does not apply to our syndicated shows.</p>

                        <p>Nevertheless, PVL does require some minimum specifications be met before your podcast can be listed on our web site. All shows/podcasts syndicated on PVL are required to:</p>

                        <ul>
                            <li><b>Have a backlog of at least 3-5 episodes before joining.</b> We want to give our visitors a library of previous content that they can review to get a feel for the show and its content, and to prepare them for future episodes. We don\'t want podcasts to be on PVL before their first episode even exists!</li>

                            <li><b>Appropriately flag all age-inappropriate content.</b> Note that content such as adult language and discussion topics is allowed on podcasts hosted by PVL, provided that it is flagged appropriately, either in the name of the podcast (if all episodes are 18+) or in the individual episode title. PVL may remove individual episodes or entire podcasts from its site if it feels that the content presented is too extreme, libelous, or otherwise harmful to our audience.</li>

                            <li><b>Provide a logo graphic and URL for syndication.</b> These are the two basic requirements that we need from all podcasts in order to syndicate them. The first is obvious; each show must have a 150x150 square logo graphic for our site. The second, a syndication URL, can be one of many things: an RSS feed URL (easily acquired by services like Libsyn), a YouTube playlist URL, SoundCloud URL, or other location where your episodes can be pulled automatically by our system.</li>
                        </ul>

                        <p>If you have any questions about this submission process, just contact our leadership at <a href="mailto:pr@ponyvillelive.com">pr@ponyvillelive.com</a>. Thank you again for your submission, and we look forward to working with you and your team!</p>
                    ',
                )),
            ),
        ),

        'profile' => array(
            'legend' => 'Basic Details',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'description' => array('textarea', array(
                    'label' => 'Podcast Description',
                    'description' => 'Tell us about what you do in the pony community, what projects you\'ve worked with, or how you want to contribute in the future.',
                    'class' => 'full-width half-height',
                )),

                'country' => array('select', array(
                    'label' => 'Country of Broadcast',
                    'multiOptions' => \PVL\Internationalization::getCountryLookup(),
                    'default' => '',
                )),

                'web_url' => array('text', array(
                    'label' => 'Web Site Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                )),

                'image_url' => array('file', array(
                    'label' => 'Avatar (150x150 PNG)',
                    'description' => 'This is the small image that appears on your profile. Images should be under 150x150px in size. Larger images will automatically be scaled.',
                )),

                'banner_url' => array('file', array(
                    'label' => 'Promotional Banner (600x300 PNG), Optional',
                    'description' => 'This image will be shown on Twitter and in our homepage rotator when new episodes are posted. Images should be 600x300.',
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

                'livestream_url' => array('text', array(
                    'label' => 'LiveStream Address',
                    'class' => 'half-width',
                    'filters' => array('WebAddress'),
                    'placeholder' => 'http://livestream.com/username',
                )),

            ),
        ),

        'submit_grp' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Submit Station',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-large',
                )),
            ),
        ),

    ),
);