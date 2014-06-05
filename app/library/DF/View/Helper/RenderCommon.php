<?php
/**
 * View Helper that allows a current view to pull a template from the common templates directory.
 */

namespace DF\View\Helper;
class RenderCommon extends \Zend_View_Helper_Abstract
{
    public function renderCommon($target, $vars = array())
    {
        $config = \Zend_Registry::get('config');
        
        $assign_vars = array_merge((array)$this->view->getVars(), $vars);
        
        $view_renderer = \DF\Application\Bootstrap::getNewView(FALSE);
        $view = $view_renderer->view;
        
        $view->setScriptPath($config->application->resources->layout->commonTemplates);
        $view->assign((array)$assign_vars);
        return $view->render($target.'.phtml');
    }
}