<?php
// Assemble select list of stations.
$stations = array();
$all_stations = \Entity\Station::fetchArray();

foreach($all_stations as $station)
{
    if ($station['category'] == 'audio')
    {
        $stations[$station['short_name']] = '<b>'.$station['name'].'</b> - '.$station['genre'];
    }
}

return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'about' => array(
            'legend' => 'About Song Submissions',
            'elements' => array(

                'about_text' => array('markup', array(
                    'markup' => '
                        <p>On behalf of the Ponyville Live! family of stations, thank you for your interest in submitting your music to the Ponyville Live! network. We are happy to work with the amazingly talented musicians and other creative fans in this community, and we hope that by playing your music on our stations, we can help bring more attention to your work.</p>

                        <p>Just so we\'re on the same page, here are just a few guidelines about our music submission process and notes about what to expect once your song is submitted to our network:</p>

                        <ul>
                            <li><b>Only upload music you created yourself:</b> This form is only for musicians themselves to upload their own content for playback on Ponyville Live! stations. Don\'t submit a song you found on YouTube or downloaded elsewhere.</li>

                            <li><b>Stations may not add all music:</b> Each station can use their own discretion when choosing to add a song submitted through this web site to their rotation. They may not find every song to be a great fit for their station\'s playlist. Don\'t take it personally!</li>

                            <li><b>Submitting a song gives our stations permissions to play it:</b> This should go without saying, but by submitting a song through this service, you are granting our radio stations a permanent license to play the song in their rotations. Radio stations only periodically stream music, and do not directly offer the files for listeners to download.</li>
                        </ul>

                        <p>If you have any questions about this submission process, contact our team at <a href="mailto:pr@ponyvillelive.com">pr@ponyvillelive.com</a>. Thank you again for your submission!</p>
                    ',
                )),
            ),
        ),

        'metadata' => array(
            'legend' => 'Song Metadata',
            'elements' => array(

                'song_url' => array('file', array(
                    'label' => 'Song to Upload',
                    'description' => 'Songs should be MP3s, with a bitrate of at least 128kbps. Maximum file size is 20MB.',
                )),

                'title' => array('text', array(
                    'label' => 'Song Title',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'artist' => array('text', array(
                    'label' => 'Song Artist Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

            ),
        ),

        'stations' => array(
            'legend' => 'Station Selection',
            'elements' => array(

                'stations' => array('multiCheckbox', array(
                    'label' => 'Select Stations',
                    'multiOptions' => $stations,
                    'required' => true,
                )),

            ),
        ),

        'submit_grp' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Submit Song',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-large',
                )),
            ),
        ),

    ),
);