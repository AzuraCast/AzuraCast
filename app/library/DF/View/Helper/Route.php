<?php
/**
 * Better URL Routing
 */

namespace DF\View\Helper;
class Route extends HelperAbstract
{
    public function route($params)
    {
        return \DF\Url::route($params, $this->di);
    }
}