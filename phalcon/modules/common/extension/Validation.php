<?php

namespace Baseapp\Extension;

/**
 * Validation
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class Validation extends \Phalcon\Validation
{

    /**
     * Translate the default message for validator type
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $type validator type
     *
     * @return string
     */
    public function getDefaultMessage($type)
    {
        // Translate dafault messages
        return __($this->_defaultMessages[$type]);
    }

}
