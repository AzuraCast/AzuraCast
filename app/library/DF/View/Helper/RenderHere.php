<?php
/**
 * View Helper that allows a current view to use the same routing mechanism for its internal template rendering that is used by the the MVC controller action.
 */

namespace DF\View\Helper;

class RenderHere extends HelperAbstract
{
    public function renderHere($target, $outside_controller_dir = FALSE, $vars = array())
    {
        if (!$outside_controller_dir)
            $target = $this->view->getControllerName().'/'.$target;

        return $this->view->partial($target, $vars);
    }
}