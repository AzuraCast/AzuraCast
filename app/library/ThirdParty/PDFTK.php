<?php
/**
 * PDFTK: Static library for interfacing with the PDF Toolkit (pdftk) application.
 */

define('PDFTK_BIN_NAME', 'pdftk.exe');

class PDFTK
{
	/**
	 * Accepts an input array of files and produces a single output file.
	 */
	public static function merge($input_files, $output_file)
	{
		$input_files = (is_array($input_files)) ? $input_files : array($input_files);
		$files_to_combine = array();
		foreach($input_files as $input_file)
		{
			if (file_exists($input_file))
			{
				$files_to_combine[] = escapeshellarg($input_file);
			}
		}
		
		$args = implode(' ', $files_to_combine).' cat output '.escapeshellarg($output_file).' dont_ask';
		
		try
		{
			self::call($args);
			return TRUE;
		}
		catch(Exception $e)
		{
			return FALSE;
		}
	}
	
	public static function call($args = '')
	{
		$exe_dir = dirname(__FILE__);
		$exe_path = $exe_dir.DIRECTORY_SEPARATOR.PDFTK_BIN_NAME;
		
		if (file_exists($exe_path))
		{
			chdir($exe_dir);
			$command = escapeshellcmd(PDFTK_BIN_NAME).' '.$args;
			
			system($command, $return_val);
			return $return_val;
		}
		else
		{
			throw new Exception('PDFTK binary does not exist in the same folder as the manager class.');
		}
	}
}