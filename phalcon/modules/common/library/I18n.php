<?php

namespace Baseapp\Library;

/**
 * Lang Library
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class I18n
{

    private $_config = array();
    protected $_cache = array();
    private static $_instance;

    /**
     * Singleton pattern
     *
     * @package     base-app
     * @version     2.0
     *
     * @return I18n instance
     */
    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new I18n;
        }

        return self::$_instance;
    }

    /**
     * Private constructor - disallow to create a new object
     *
     * @package     base-app
     * @version     2.0
     */
    private function __construct()
    {
        // Overwrite _config from config.ini
        if ($_config = \Phalcon\DI::getDefault()->getShared('config')->i18n) {
            foreach ($_config as $key => $value) {
                $this->_config[$key] = $value;
            }
        }
    }

    /**
     * Private clone - disallow to clone the object
     *
     * @package     base-app
     * @version     2.0
     */
    private function __clone()
    {

    }

    /**
     * Set the language
     *
     * @package     base-app
     * @version     2.0
     *
     * @param mixed $lang language code
     *
     * @return string
     */
    public function lang($lang = null)
    {
        // Normalize the language
        if ($lang) {
            $this->_config['lang'] = strtolower(str_replace(array(' ', '_'), '-', $lang));
        }

        return $this->_config['lang'];
    }

    /**
     * Load language from the file
     *
     * @package     base-app
     * @version     2.0
     *
     * @param mixed $lang language code
     *
     * @return array
     */
    private function load($lang)
    {
        // Load from the cache
        if (isset($this->_cache[$lang])) {
            return $this->_cache[$lang];
        }

        $parts = explode('-', $lang);
        $subdir = implode(DIRECTORY_SEPARATOR, $parts);

        // Search for /en/gb.php, /en-gb.php, /en.php or gb.php
        foreach (array($subdir, $lang, $parts) as $tail) {
            if (!is_array($tail)) {
                $tail = array($tail);
            }

            foreach ($tail as $found) {
                $path = $this->_config['dir'] . $found . '.php';
                if (file_exists($path)) {
                    $messages = require $path;
                    // Stop searching
                    break;
                }
            }
        }

        $translate = new \Phalcon\Translate\Adapter\NativeArray(array(
            "content" => isset($messages) ? $messages : array()
        ));

        return $this->_cache[$lang] = $translate;
    }

    /**
     * Get messages from the cache
     *
     * @package     base-app
     * @version     2.0
     *
     * @return array
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Translate message
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $string string to translate
     * @param array $values replace substrings
     *
     * @return string translated string
     */
    public function _($string, array $values = null)
    {
        $translate = $this->load($this->_config['lang']);
        $string = $translate->query($string, $values);

        return empty($values) ? $string : strtr($string, $values);
    }

}
