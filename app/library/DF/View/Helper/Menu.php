<?php
namespace DF\View\Helper;
class Menu extends HelperAbstract
{
    /**
     * @return Zend_Navigation
     */
    protected function _getMenu($name = 'default')
    {
        static $menus;

        if( !isset($menus) )
        {
            $application = \Zend_Registry::get('application');
            $menus = $application->getBootstrap()->getResource('menu');
        }

        return $menus->getMenu($name);
    }

    public function menu($name = 'default')
    {
        $menu = $this->_getMenu($name);

        return $menu;
    }
}