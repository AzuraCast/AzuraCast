<?php
namespace Modules\Api;

class Module extends \DF\Phalcon\Module
{
    public function __construct()
    {
        $this->setModuleInfo('Api', __DIR__);
    }
}
