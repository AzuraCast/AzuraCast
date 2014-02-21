<?php
namespace DF;

class Zip
{
	protected $_zip;
	
	public function __construct($filename)
	{
		if (!defined('DF_ZIP_LOADED'))
		{
			require 'dUnzip2.php';
			require 'dZip.php';
			
			define('DF_ZIP_LOADED', TRUE);
		}
		
		$this->_zip = new \dZip($filename);
	}
	
	public function addDirectory($source, $target='', $ignore_directories = array())
	{
		if (is_dir($source))
		{
			$d = dir($source);
			while (FALSE !== ($entry = $d->read()))
			{
				if ($entry == '.' || $entry == '..')
				{
					continue;
				}
			   
				$Entry = $source.'/'.$entry;
				 
				if (!in_array($entry, $ignore_directories))
				{    
					if (is_dir($Entry))
					{
						$this->addDirectory($Entry, $target.$entry.'/', $ignore_directories);
						continue;
					}
					
					$this->addFile($Entry, $target.$entry);
				}
			}
			$d->close();
		}
		else
		{
			$this->addFile($source, $target.$source);
		}
	}
	
	public function addFile($source, $target)
	{
		$this->_zip->addFile($source, $target);
	}
	
	public function save()
	{
		$this->_zip->save();
	}
}