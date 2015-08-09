<?php
namespace Modules\Url;

class Module extends \DF\Phalcon\Module
{
    public function __construct()
    {
        $this->setModuleInfo('Url', __DIR__);
    }
}
