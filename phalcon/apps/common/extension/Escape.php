<?php

namespace Baseapp\Extension;

/**
 * Escape filter - convert characters to HTML entities
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class Escape
{

    /**
     * Add the new filter
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $string string to filtering
     *
     * @return string filtered string
     */
    public function filter($string)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES);
    }

}
