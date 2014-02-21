<?php
namespace DF;
class Navigation extends \Zend_Navigation
{
    public function depthOfMenu($nav_item = NULL)
    {
        $nav_item = (is_null($nav_item)) ? $this : $nav_item;

        $depth_num = 1;
        $children_depths = array();

        foreach($nav_item as $item)
        {
            if( $item->isActive(true) && $item->hasChildren() )
            {
                foreach($item as $child)
                {
                    $children_depths[] = $this->depthOfMenu(array($child));
                }
            }
        }

        if ($children_depths)
        {
            $depth_num += max($children_depths);
        }

        return $depth_num;
    }
    
    public function hasActiveChildren($levels = 0, \Zend_Navigation_Page $items = null)
    {
        if( $items == null )
            $items = $this;

        foreach( $items as $item )
        {
            if( $item->isActive(true) && $item->hasChildren() )
            {
                if( $levels != 0 )
                    $this->hasActiveChildren($levels - 1, $item);
                else
                    return true;
            }
        }

        return false;
    }

    public function render(array $options = array())
    {
        $defaults = array(
            'levels' => -1,
        );

        $options = array_merge($defaults, $options);

        $items = array();
        foreach( $this as $item )
        {
            $items[] = $this->_navRender($item, (int)$options['levels'] - 1, '', 'here');
        }

        if( empty($items) )
        {
            return "";
        }
        else
        {
            $retval = '<ul>'.implode("\n", $items).'</ul>';
			return $retval;
        }
    }

    public function renderActiveChildren(array $options = array())
    {
        $defaults = array(
            'levels' => -1,
        );
        $options = array_merge($defaults, $options);

        $childrenRetVal = '';
        foreach( $this as $item )
        {
            if( $item->isActive(true) && $item->hasChildren() )
            {
                foreach( $item as $child )
                {
                    $childrenRetVal .= $this->_navRender($child, $options['levels'] - 1, '', 'here');
                }
            }
        }
        
        if( $childrenRetVal == '' )
            return "";
        else
            $retval .= $childrenRetVal;

        return $retval;
    }
    
    public function getActiveChildren(array $options = array())
    {
        $defaults = array(
            'levels' => -1,
        );
        $options = array_merge($defaults, $options);
        
        $children = array();
        foreach($this as $item)
        {
            if( $item->isActive(true) && $item->hasChildren() )
            {
                foreach($item as $child)
                {
                    $children[] = $child;
                }
            }
        }
        
        return ($children) ? $children : array();
    }

    protected function _navRender(\Zend_Navigation_Page $page, $levels = -1, $class = '', $currentClass = 'here')
    {
        if( !self::_checkPermission($page) )
            return '';

		$class = str_replace('here', '', $class);

        if( self::_isPageActive($page) )
        {
            $class = $class . " " . $currentClass;
            if (!$page->hasChildren()) $class = $class . " nochildren";
        }
		
		$retval = '';
		
		$page_label = $page->getLabel();
		$retval .= ($page_label) ? '<a href="'.$page->getHref().'" class="'.$class.'">'.$page_label.'</a>' : '';

        if( $page->hasChildren() && $levels != 0 )
        {
			$sub_retval = '';
            foreach( $page as $child )
            {
                $sub_retval .= $this->_navRender($child, $levels - 1, $class, $currentClass);
            }
			
			if (!empty($sub_retval))
				$retval .= '<ul>'.$sub_retval.'</ul>';
        }
        
        if (isset($page->show_only_with_subpages) && !$sub_retval)
            return '';
		
		if (!empty($retval))
			return '<li>'.$retval.'</li>';
		else
			return '';
    }
    
    protected static function _checkPermission(\Zend_Navigation_Page $page)
    {
		if(isset($page->permission))
		{
			$acl = Acl::getInstance();
			
			if (isset($page->is_dept) && $page->is_dept && method_exists($acl, 'isAllowedInDepartment'))
				return $acl->isAllowedInDepartment($page->permission);
			else
				return $acl->isAllowed($page->permission);
		}
		return true;
    }

    protected static function _isPageActive(\Zend_Navigation_Page $page)
    {
        if( $page->isActive(true) )
            return true;

        static $current_params;

        if($page instanceof \Zend_Navigation_Page_Mvc)
        {
            if( !isset($current_params) )
                $current_params = \Zend_Controller_Front::getInstance()->getRequest()->getParams();

            $menu_params = $page->getParams();

            if ($param = $page->getModule())
            {
                $menu_params['module'] = $param;
            }
            if ($param = $page->getController())
            {
                $menu_params['controller'] = $param;
            }
            if ($param = $page->getAction())
            {
                $menu_params['action'] = $param;
            }

            $intersection = array_intersect_assoc($menu_params, $current_params);
            $intersection_diff = array_diff_assoc($menu_params, $intersection);

            if( empty($intersection_diff) )
            {
                $page->setActive();
            }
        }
        elseif($page instanceOf \Zend_Navigation_Page_Uri)
        {
            if( isset($_SERVER['REQUEST_URI']) && preg_match("#^".preg_quote($page->getHref(), '#')."#i", $_SERVER['REQUEST_URI']) )
            {
                $page->setActive();
            }
        }

        return $page->isActive(true);
    }
}