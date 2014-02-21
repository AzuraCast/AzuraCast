<?php

class Services_Twilio_Rest_Conference
    extends Services_Twilio_InstanceResource
{
    protected function init()
    {
        $this->setupSubresources(
            'participants'
        );
    }
}
