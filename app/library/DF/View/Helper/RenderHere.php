<?php
/**
 * View Helper that allows a current view to use the same routing mechanism for its internal template rendering that is used by the Zend_Controller_Action.
 */

namespace DF\View\Helper;
class RenderHere extends \Zend_View_Helper_Abstract
{
    public function renderHere($target,$outside_controller_dir = FALSE)
    {
        // Get the current path used by the current template.
        $view_renderer = new \Zend_Controller_Action_Helper_ViewRenderer();
        $view_renderer->init();
        
        $view_renderer->setRender($target, NULL, $outside_controller_dir);
        $path = $view_renderer->getViewScript();
        
        $return_value = $this->view->render($path);     
        return $return_value;
    }
}