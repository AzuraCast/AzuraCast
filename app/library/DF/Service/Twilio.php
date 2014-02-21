<?php
namespace DF\Service;

class Twilio
{
	public static function getInstance()
	{
		static $twilio;

		if (!$twilio)
		{
			$settings = self::loadSettings();

			$http = new \Services_Twilio_TinyHttp('https://api.twilio.com', array('curlopts' => array(
			    CURLOPT_SSL_VERIFYPEER => false
			)));
			$twilio = new \Services_Twilio($settings['account_id'], $settings['auth_token'], '2010-04-01', $http);
		}

		return $twilio;
	}

	public static function loadSettings()
	{
		static $settings;

		if (!$settings)
		{
			$config = \Zend_Registry::get('config');
			$settings = $config->services->twilio->toArray();
		}

		return $settings;
	}

	public static function sms($number, $message)
	{
		$client = self::getInstance();
		$settings = self::loadSettings();

		$number = preg_replace("/[^0-9]/", "", $number);
		if (strlen($number) > 10)
			$number = substr($number, -10);
		
		if ($number)
		{
			return $client->account->sms_messages->create(
				$settings['from_number'],
				$number,
				$message
			);
		}
	}
}