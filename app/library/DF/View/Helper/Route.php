<?php
/**
 * Better URL Routing
 */

namespace DF\View\Helper;
class Route extends \Zend_View_Helper_Abstract
{
	public function route()
	{
		$func_args = func_get_args();
		return call_user_func_array('\DF\Url::route', $func_args);
	}
}