<?php
namespace DF\Validate;
class Phone extends \Zend_Validate_Abstract
{
    const PHONE = 'phone';

    protected $_messageTemplates = array(
        self::PHONE => "'%value%' is not a phone number"
    );

    /**
     * In case DF_Filter_Phone class is not present, provide our own phone regex
     * @var string
     */
    protected static $_phoneRegex = '#^([\+\-]*\d*)[\W\-\.]*(\d{3})[\W\-\.]*(\d{3})[\W-\.]*(\d{4})[\W]*(ext[\.\W]*(\d+)|)$#i';

    public function isValid($value)
    {
        $this->_setValue(trim($value));
        
        $matches = array();
        if( !preg_match(self::getPhoneRegex(), trim($value), $matches) )
        {
            $this->_error(self::PHONE);
            return false;
        }

        return true;
    }

    public static function getPhoneRegex()
    {
        if( class_exists('\DF\Filter\Phone') )
            return \DF\Filter\Phone::getPhoneRegex();
        else
            return self::$_phoneRegex;
    }
}