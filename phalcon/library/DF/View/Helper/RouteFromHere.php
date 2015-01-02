<?php
/**
 * Better URL Routing
 */

namespace DF\View\Helper;
class RouteFromHere extends \Zend_View_Helper_Abstract
{
    public function routeFromHere()
    {
        $func_args = func_get_args();
        return call_user_func_array('\DF\Url::routeFromHere', $func_args);
    }
}