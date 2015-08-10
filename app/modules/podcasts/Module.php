<?php
namespace Modules\Podcasts;

class Module extends \DF\Phalcon\Module
{
    public function __construct()
    {
        $this->setModuleInfo('Podcasts', __DIR__);
    }
}
