<?php
/**
 * Better URL Routing
 */

namespace DF\View\Helper;
class RouteFromHere extends HelperAbstract
{
    public function routeFromHere($params)
    {
        return \DF\Url::routeFromHere($params, $this->di);
    }
}