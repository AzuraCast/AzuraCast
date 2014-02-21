<?php

class Services_Twilio_Rest_Accounts
    extends Services_Twilio_ListResource
{
    public function create(array $params = array())
    {
        return parent::_create($params);
    }
}
