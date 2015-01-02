<?php
namespace DF\View\Helper;
class Button extends \Zend_View_Helper_Abstract
{
    /**
     * @return A string containing the icon's image tag.
     */
    public function button($params)
    {
        $defaults = array(
            'type'          => 'link',
            'class'         => '',
        );
        $params = array_merge($defaults, $params);
        
        $params['class'] .= ' btn ui-button';
                
        $button_icon = (isset($params['icon'])) ? $this->view->icon($params['icon']) : '';
        $button_type = $params['type'];
        $button_text = $params['text'];
        unset($params['text'], $params['icon']);
        
        switch($button_type)
        {
            case "button":
            case "submit":
                $button_string = array();
                foreach($params as $param_key => $param_val)
                {
                    $button_string[] = $param_key.'="'.$param_val.'"';
                }
                return '<button '.implode(' ', $button_string).'>'.$button_icon.$button_text.'</button>';
            break;
            
            case "link":
            case "small":
            case "mini":
            case "large":
            case "block":
            default:
                unset($params['type']);
                $defaults = array(
                    'href'      => '#',
                );
                $params = array_merge($defaults, $params);
                
                if ($button_type != "link")
                    $params['class'] .= ' btn-'.$button_type;
                
                $button_string = array();
                foreach ($params as $param_key => $param_val)
                {
                    $button_string[] = $param_key.'="'.$param_val.'"';
                }
                return '<a '.implode(' ', $button_string).'>'.$button_icon.$button_text.'</a>';
            break;
        }
    }
}