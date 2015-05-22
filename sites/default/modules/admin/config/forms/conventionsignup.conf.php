<?php
return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'con_info' => array(
            'legend' => 'Convention Details',
            'elements' => array(

                'about_con' => array('markup', array(
                    'label' => 'About the Convention',
                    'markup' => '<p>Placeholder content. Convention details should appear in this space.</p>',
                )),

                'about_con_coverage' => array('markup', array(
                    'label' => 'About Convention Coverage',
                    'markup' => '
                        <p>Ponyville Live! is proud to provide convention coverage services to events around the globe. Because we don\'t charge conventions for our services, we depend on our station members, friends and affiliates to assist in operating our cameras and monitoring our equipment while we are at the convention.</p>

                        <p>At PVL, we have a passion for what we do: providing a very valuable service to the Brony community. Whether we\'re streaming a convention or simply recording it for later archival, we strive to provide the best possible coverage for the largest percentage of each convention as we can. As a PVL press representative, this means always putting your "best foot forward", and being professional and courteous. While working in panel rooms, we request that you keep your cell phone silenced and not wear any costumes that would distract from the main subject of the recording.</p>

                        <p>At this convention, your responsibilities may include:</p>
                        <dl>
                            <dt>Camera Operation (Always Required)</dt>
                            <dd>Camera operators sign up for panels they are interested in attending at the beginning of every day at the convention. The number of panels each person is asked to attend depends on the total staff available at the convention, and the number of panels to be recorded. All cameras operated by PVL are Canon consumer and "prosumer" models. Operating a camera doesn\'t require an extensive knowledge of the camera; most settings are set up ahead of time, so most of the job of camera operation is ensuring that the camera is recording during panels, that the audio levels are suitable, and that the camera\'s storage does not fill up.</dd>
                            <dt>Table Promotion (Sometimes Required)</dt>
                            <dd>PVL often has a table on the convention premises, where we can promote the network and store our equipment when it isn\'t in use elsewhere. In order to avoid having to secure our equipment several times during the event, we prefer that someone always be available to watch the table and our equipment, and refer visitors to our web site for more information. This is a more infrequent request, as we often have administrators watching the table, and the only time the table will be unattended is when they are unavailable or need a break.</dd>
                            <dt>Other Duties (Sometimes Required)</dt>
                            <dd>Other duties that we may request of you include being available to carry equipment to and from special interviews, relocating tripods and cameras if needed, and communicating messages to convention staffers.</dd>
                        </dl>

                        <p>In exchange for your assistance with convention coverage, we provide a complimentary "press pass" that grants full priority access to the convention. We also can assist in providing lodging opportunities and will often provide food during the event. A specific outline of convention expenses is listed below.</p>

                        <dl>
                            <dt>Transportation to/from Convention: You Pay</dt>
                            <dd>You are entirely responsible for your transportation to and from the convention premises, whether via flying or driving. If you are flying or taking a bus in, and the hotel does not offer a free shuttle from the airport, contact us and we may be able to assist with arranging or providing a ride.</dd>

                            <dt>Convention Registration Fees: We Pay</dt>
                            <dd>As long as you are approved to serve as a PVL press representative, you are not required to pay any portion of convention registration fees. Some conventions have special events that press are not normally invited to (i.e. VIP dinners), and you are responsible for paying for these if you wish to attend.</dd>

                            <dt>Lodging at the Convention Hotel: Split (See Below)</dt>
                            <dd>PVL often reserves a number of hotel rooms at or near the convention facility, and we often have space available on beds or on our hotel room floors. Unfortunately, we cannot guarantee a bed for any press representatives ahead of time, so if you would like to guarantee that you will have a bed, you should reserve a separate hotel room.</dd>

                            <dt>Food During the Convention: Split (See Below)</dt>
                            <dd>You should always be prepared to supply every meal during a convention in case it is required. If you are working with PVL during a lunch or dinner time period, we will often order enough food to feed the entire crew. If you have special dietary concerns, please note those in your information below.</dd>
                        </dl>

                        <p><b>Important Note:</b> Unless you are able to cover all convention attendance fees, DO NOT make travel arrangements for this convention without a confirmation of your press pass approval from the PVL Administration. You will be contacted via e-mail with updates about our convention coverage.</p>
                    ',
                )),

                'special_con_notes' => array('markup', array(
                    'label' => 'Special Notes for This Convention',
                    'markup' => '',
                )),

            ),
        ),

        'signup_info' => array(
            'legend' => 'Your Signup Information',
            'elements' => array(

                'legal_name' => array('text', array(
                    'label' => 'Legal Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'pony_name' => array('text', array(
                    'label' => 'Badge Name (or "Pony Name")',
                    'description' => 'This name will be printed on your press badge. It can be the same as your legal name if you wish.',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'phone' => array('text', array(
                    'label' => 'Cell Phone Number',
                    'description' => 'A phone number where you can be reached during the convention time.',
                    'required' => true,
                )),

                'email' => array('text', array(
                    'label' => 'E-mail Address',
                    'validators' => array('EmailAddress'),
                    'description' => 'We will send press approval information and pre-convention updates to this address.',
                    'required' => true,
                )),

                'pvl_affiliation' => array('radio', array(
                    'label' => 'Your Affiliation with Ponyville Live!',
                    'multiOptions' => array(
                        'core'      => 'PVL Network Administrator',
                        'station'   => 'Radio Station / Video Stream Staff',
                        'podcast'   => 'Podcast / Show Host or Staff',
                        'affiliate' => 'PVL Affiliate Member',
                        'prior'     => 'Previous PVL Camera Operator',
                        'other'     => 'Other',
                    ),
                )),

                'travel_notes' => array('textarea', array(
                    'label' => 'Travel Itinerary',
                    'description' => 'Tell us how you plan to travel to the convention, and when you plan to arrive and depart. Also note if you require any assistance with any portion of your trip.',
                    'class' => 'half-width half-height',
                )),

                'accommodation_notes' => array('textarea', array(
                    'label' => 'Special Accommodation Notes (Optional)',
                    'description' => 'If you require any special lodging or food considerations, please let us know here.',
                    'class' => 'half-width half-height',
                )),

            ),
        ),

        'submit_grp' => array(
            'elements' => array(
                'submit' => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Submit Signup Registration',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-large',
                )),
            ),
        ),

    ),
);