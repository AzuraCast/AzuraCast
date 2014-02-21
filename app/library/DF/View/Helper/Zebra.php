<?php
/**
 * Zebra-striping!
 */

namespace DF\View\Helper;
class Zebra extends \Zend_View_Helper_Abstract
{
	protected static $_zebras = array();
	
	public function zebra($zebra_func = NULL, $zebra_set = 'default')
	{
		if (!isset(self::$_zebras[$zebra_set]))
			self::$_zebras[$zebra_set] = array();
		
		$zebra_current = self::$_zebras[$zebra_set];
		
		switch($zebra_func)
		{
			case 'repeat':
				$return_val = ($zebra_current == 1) ? 'odd' : 'even';
			break;
				
			case 'reset':
				$zebra_current = 0;
				$return_val = NULL;
			break;
			
			default:
				$zebra_current = 1 - $zebra_current;
				$return_val = ($zebra_current == 1) ? 'odd' : 'even';
			break;
		}
		
		self::$_zebras[$zebra_set] = $zebra_current;
		return $return_val;
	}
}