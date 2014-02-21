<?php

class Services_Twilio_Rest_AvailablePhoneNumbers
    extends Services_Twilio_ListResource
{
    public function getLocal($country)
    {
        $curried = new Services_Twilio_PartialApplicationHelper();
        $curried->set(
            'getList',
            array($this, 'getList'),
            array($country, 'Local')
        );
        return $curried;
    }
    public function getTollFree($country)
    {
        $curried = new Services_Twilio_PartialApplicationHelper();
        $curried->set(
            'getList',
            array($this, 'getList'),
            array($country, 'TollFree')
        );
        return $curried;
    }
    public function getList($country, $type, array $params = array())
    {
        return $this->retrieveData("$country/$type", $params);
    }
}
