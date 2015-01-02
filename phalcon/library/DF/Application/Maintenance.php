<?php
namespace DF\Application;
class Maintenance
{
    public static function render($message, $title = NULL)
    {
        $layout = self::getLayout();
        
        if ($title !== NULL)
            $layout->getView()->headTitle($title);
        
        $layout->content = $message;
        return $layout->render();
    }
    
    public static function display($message, $title = NULL)
    {
        echo self::render($message, $title);
    }
    
    public static function getLayout()
    {
        static $layout;
        
        if ($layout === NULL)
        {
            $registry = \Zend_Registry::getInstance();
            if (isset($registry['config']))
                $config = $registry['config'];
            else
                $config = $_GLOBALS['config'];
            
            // Initialize Zend routing.
            $front = \Zend_Controller_Front::getInstance();
            $front->setRequest(new \Zend_Controller_Request_Http);
            
            // Special handling for .php scripts being accessed directly.
            if (stristr($_SERVER['REQUEST_URI'], '.php') !== FALSE)
                $front->setBaseUrl(substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')));
            
            // Set up maintenance layout.
            $layout = new \Zend_Layout();
            $layout->setLayoutPath($config->application->resources->layout->layoutPath);
            $layout->getView()->assign(array(
                'config'        => $config,
            ));
            $layout->setLayout('maintenance');
        }
        
        return $layout;
    }
}