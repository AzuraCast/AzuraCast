<?php
namespace DF\Filter;
class Phone implements \Zend_Filter_Interface
{
    /**
     * Output format string
     *
     * @var string
     */
    protected $_format = '%countrycode% %areacode% %centralofficecode%-%linenumber% %extension%';

    /**
     * Formatting options to apply to a token before placing in the format string
     *
     * @var array
     */
    protected $_subFormats = array(
        '%areacode%' => '(%areacode%)',
        '%extension%' => 'ext. %extension%',
    );

    /**
     * Values to be replaced if one of the following tokens is empty
     *
     * @var array
     */
    protected $_ifEmpty = array(
        '%areacode%' => '',
        '%extension%' => '',
        '%countrycode%' => '',
    );

    /**
     * Common delimiter between groupings of numerals in a phone number
     *
     * @var string
     */
    protected static $_phoneRegex_delim = "[\W]*";
    
    /**
     * Portions of the phone regular expression. Expressed as an array for ease
     * of maintenence. Not intended to be altered.
     *
     * @var array
     */
    protected static $_phoneRegex_pieces = array(
        '%countrycode% + %areacode%' => '(([\s\+\-\d]+|)[\W]*(\d{3})|)', //country code requires an area code
        '%centralofficecode%' => '(\d{3})',
        '%linenumber%' => '(\d{4})',
        '%extension%' => '((ext[\.\s]*)(\d+)|)'
    );
    
    /**
     * List the numerical indicies for the particular groups in the $matches array
     * returned by preg_match().
     *
     * @var array
     */
    protected $_groups = array(
        '%countrycode%' => 2,
        '%areacode%' => 3,
        '%centralofficecode%' => 4,
        '%linenumber%' => 5,
        '%extension%' => 8,
    );

    /**
     * Valid replaceable tokens are:
     *
     * - %countrycode%
     * - %areacode%
     * - %centralofficecode%
     * - %linenumber%
     * - %extension%
     *
     *
     * @param string $phoneFormat
     * @param array $subFormats
     * @param array $ifEmpty
     */
    public function __construct($phoneFormat = null, array $subFormats = null, array $ifEmpty = null)
    {
        if( $phoneFormat !== null )
            $this->_format = $phoneFormat;

        if( $subFormats !== null )
            $this->_subFormats = $subFormats;

        if( $ifEmpty !== null )
            $this->_ifEmpty = $ifEmpty;
    }

    /**
     * Returns the compiled phone regular expression
     *
     * @staticvar string $regex Cached regex string
     * @return string
     *
     */
    public static function getPhoneRegex()
    {
        static $regex;

        if( !$regex )
            $regex = '#^' . implode(self::$_phoneRegex_delim, self::$_phoneRegex_pieces) . '$#i';
        
        return $regex;
    }

    /**
     * Formats a given phone number according to the rules given in the constructor
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $value = trim($value);
        
        $matches = array();
        if( preg_match(self::getPhoneRegex(), $value, $matches) != 1 )
        {
            return $value;
        }

        $search = array();
        $replace = array();
        foreach( $this->_groups as $key => $value )
        {
            $search[$key] = $key;
            if( isset($matches[$value]) )
                $replace[$key] = trim($matches[$value]);
            else
                $replace[$key] = '';
        }
        
        foreach( $this->_subFormats as $key => $format )
        {
            $match = isset($matches[$this->_groups[$key]]) ? $matches[$this->_groups[$key]] : '';

            if( trim($match) != '' )
            {
                $replace[$key] = str_replace($key, $match, $format);
            }
            else
            {
                $replace[$key] = '';
            }
        }

        foreach( $this->_ifEmpty as $key => $value )
        {
            if( !isset($replace[$key]) || $replace[$key] == '' )
                $replace[$key] = $value;
        }

        return trim(str_replace($search, $replace, $this->_format));
    }
}