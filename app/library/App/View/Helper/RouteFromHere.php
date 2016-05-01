<?php
/**
 * Better URL Routing
 */

namespace App\View\Helper;
class RouteFromHere extends HelperAbstract
{
    public function routeFromHere($params)
    {
        return $this->di['url']->routeFromHere($params);
    }
}