<?php
/**
 * View Helper that allows a current view to pull a template from the common templates directory.
 */

namespace App\View\Helper;
class RenderCommon extends HelperAbstract
{
    public function renderCommon($target, $vars = array())
    {
        $previous_partials_dir = $this->view->getPartialsDir();

        $new_partials_dir = $this->view->getLayoutsDir().'/shared/';
        $this->view->setPartialsDir($new_partials_dir);

        $partial = $this->view->partial($target, $vars);

        $this->view->setPartialsDir($previous_partials_dir);

        return $partial;
    }
}