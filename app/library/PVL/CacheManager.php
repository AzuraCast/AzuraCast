<?php
namespace PVL;

class CacheManager
{
    public static function generateSlimPlayer()
    {
        $stations = \Entity\Station::getStationsInCategories();

        $view_vars = array(
            'skin' => 'slim',
            'embed_mode' => true,
            'categories' => $stations,
        );

        $config = \Zend_Registry::get('config');

        $layout = new \Zend_Layout();
        $layout->setLayoutPath($config->application->resources->layout->layoutPath);
        $layout->setLayout('maintenance');
        $layout->getView()->assign($view_vars);
        
        $view_renderer = \DF\Application\Bootstrap::getNewView(FALSE);
        $view = $view_renderer->view;

        $tunein_script = 'default/views/scripts';
        $view_script_path = DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$tunein_script;
        $view->setScriptPath($view_script_path);

        $view->assign($view_vars);
        
        $layout->content = $view->render('player.phtml');
        $result = $layout->render();

        @file_put_contents(DF_INCLUDE_STATIC.'/api/player.html', $result);
    }
}