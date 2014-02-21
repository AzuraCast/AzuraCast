<?php

/**
 * Sample command.
 * 
 * @command-handler sample
 * @command-handler-description Sample command.
 * @command-handler-header Windows Azure SDK for PHP
 * @command-handler-header (C) RealDolmen 2011 - www.realdolmen.com
 */
class Sample
	extends Zend_Service_Console_Command
{
	/**
	 * Hello command
	 * 
	 * @command-name hello
	 * @command-description Prints Hello, World!
	 * @command-parameter-for $name Zend_Service_Console_Command_ParameterSource_Argv|Zend_Service_Console_Command_ParameterSource_Env --name|-n Required. Name to say hello to.
	 * @command-parameter-for $bePolite Zend_Service_Console_Command_ParameterSource_Argv -p Optional. Switch to enable polite mode or not.
	 * @command-example Print "Hello, Maarten! How are you?" (using polite mode):
	 * @command-example   hello -n:"Maarten" -p
	 * 
	 * @param string $name
	 */
	public function helloCommand($name, $bePolite = false)
	{
		echo 'Hello, ' . $name . '.';
		if ($bePolite) {
			echo ' How are you?';
		}
		echo "\r\n";
	}
	
	/**
	 * What time is it command
	 * 
	 * @command-name timestamp
	 * @command-description Prints the current timestamp.
	 * 
	 * @param string $name
	 */
	public function timestampCommand()
	{
		echo date();
		echo "\r\n";
	}
}

Zend_Service_Console_Command::bootstrap($_SERVER['argv']);