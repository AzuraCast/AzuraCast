<?php
/**
 * Better URL Routing
 */

namespace DF\View\Helper;
class Route extends HelperAbstract
{
    public function route()
    {
        $func_args = func_get_args();
        return call_user_func_array('\DF\Url::route', $func_args);
    }
}