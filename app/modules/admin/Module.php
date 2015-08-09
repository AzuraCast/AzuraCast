<?php
namespace Modules\Admin;

class Module extends \DF\Phalcon\Module
{
    public function __construct()
    {
        $this->setModuleInfo('Admin', __DIR__);
    }
}
