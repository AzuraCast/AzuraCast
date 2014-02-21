<?php

class Services_Twilio_Rest_Call
    extends Services_Twilio_InstanceResource
{
    public function hangup()
    {
        $this->update('Status', 'completed');
    }

    protected function init()
    {
        $this->setupSubresources(
            'notifications',
            'recordings'
        );
    }
}
