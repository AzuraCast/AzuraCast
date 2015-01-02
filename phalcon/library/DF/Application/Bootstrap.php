<?php
namespace DF\Application;
class Bootstrap extends \Zend_Application_Bootstrap_Bootstrap
{   
    public function _initView()
    {
        return self::getNewView(TRUE);
    }

    public static function getNewView($use_static = TRUE)
    {
        $view = new \Zend_View();
        
        $view->setEncoding('UTF-8');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->doctype(\Zend_View_Helper_Doctype::HTML5);
        
        $view->config = \Zend_Registry::get('config');
        
        $view->addHelperPath('DF/View/Helper', 'DF\View\Helper\\');
        
        if ($use_static)
        {
            $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
            $viewRenderer->setView($view);
        }
        else
        {
            $viewRenderer = new \Zend_Controller_Action_Helper_ViewRenderer($view);
        }
        
        return $viewRenderer;
    }
}