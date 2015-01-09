<?php
namespace Modules\Frontend;

class Module extends \DF\Phalcon\Module
{
    public function __construct()
    {
        $this->setModuleInfo('Frontend', __DIR__);
    }
}
