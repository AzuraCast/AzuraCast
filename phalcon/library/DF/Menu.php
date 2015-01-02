<?php
namespace DF;
class Menu
{
    protected $menus;

    /**
     * @param array $menus Associative array of menus
     */
    public function __construct(array $menus = array())
    {
        foreach($menus as $name => $structure)
        {
            $this->addMenu($name, $structure);
        }
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function hasMenu($name = 'default')
    {
        return isset($this->menus[$name]);
    }

    /**
     *
     * @param string $name
     * @return Zend_Navigation
     */
    public function getMenu($name = 'default')
    {
        if( $this->hasMenu($name) )
        {
            $menu = $this->menus[$name];

            if(!($menu instanceof Navigation))
                $menu = $this->menus[$name] = new Navigation($menu);
            
            return $menu;
        }
    }

    /**
     *
     * @param string $name
     * @param Zend_Navigation|array $structure
     */
    public function addMenu($name, $structure)
    {
        if(is_array($structure))
            $structure = $structure;
        
        $this->menus[$name] = $structure;
    }
}