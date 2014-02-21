<?php

class Services_Twilio_Rest_SmsMessages
    extends Services_Twilio_ListResource
{
    public function getSchema()
    {
        return array(
            'class' => 'Services_Twilio_Rest_SmsMessages',
            'basename' => 'SMS/Messages',
            'instance' => 'Services_Twilio_Rest_SmsMessage',
            'list' => 'sms_messages',
        );
    }

    function create($from, $to, $body, array $params = array())
    {
        return parent::_create(array(
            'From' => $from,
            'To' => $to,
            'Body' => $body
        ) + $params);
    }
}
