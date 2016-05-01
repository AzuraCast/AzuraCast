<?php
namespace App\Phalcon;

class Application extends \Phalcon\Mvc\Application
{
    /**
     * Bootstrap the necessary components of the Phalcon application.
     */
    public function bootstrap()
    {
        $di = $this->di;
        $module_names = $di->get('phalcon_modules');

        $modules = array();
        foreach($module_names as $module_dir => $module_name)
        {
            $modules[$module_dir] = function() use ($module_dir, $module_name, $di) {
                $mod_obj = new \App\Phalcon\Module();
                $mod_obj->setModuleInfo($module_name, APP_INCLUDE_MODULES . DIRECTORY_SEPARATOR . $module_dir);

                $mod_obj->registerAutoloaders($di);
                $mod_obj->registerServices($di);
            };
        }

        $this->registerModules($modules);
        return $this;
    }

    /**
     * Run the application and generate the resulting content.
     */
    public function run()
    {
        echo $this->handle()->getContent();

        return $this;
    }

}