<?php
namespace DF\Phalcon;

class Application extends \Phalcon\Mvc\Application
{
    /**
     * Bootstrap the necessary components of the Phalcon application.
     */
    public function bootstrap()
    {
        $phalcon_modules = $this->di->get('phalcon_modules');
        $this->registerModules($phalcon_modules);

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