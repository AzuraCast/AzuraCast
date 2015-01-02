<?php

namespace Baseapp\Extension;

/**
 * Repeat filter - remove repeating
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class Repeat
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
        return preg_replace(array('/[ ]{2,}/', '/((\r\n|\n\r|\n|\r){2,})/', '~(.?)\1{3,}~'), array(' ', "\n\n", '$1$1$1'), $string);
    }

}
