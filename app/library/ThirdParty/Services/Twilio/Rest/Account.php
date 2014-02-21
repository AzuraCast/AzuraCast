<?php

class Services_Twilio_Rest_Account
    extends Services_Twilio_InstanceResource
{
    protected function init()
    {
        $this->setupSubresources(
            'applications',
            'available_phone_numbers',
            'outgoing_caller_ids',
            'calls',
            'conferences',
            'incoming_phone_numbers',
            'notifications',
            'outgoing_callerids',
            'recordings',
            'sms_messages',
            'transcriptions'
        );

        $this->sandbox = new Services_Twilio_Rest_Sandbox(
            new Services_Twilio_CachingDataProxy('Sandbox', $this)
        );
    }
}
