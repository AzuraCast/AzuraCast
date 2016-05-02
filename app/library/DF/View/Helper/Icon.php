<?php
namespace DF\View\Helper;
class Icon extends HelperAbstract
{
    /**
     * @return A string containing the icon's image tag.
     */
    public function icon($params)
    {       
        if (!is_array($params))
            $params = array('image' => $params);
        
        // Default values for icons.
        $defaults = array(
            'type'      => 'png',
            'alt'       => '(Icon)',
            'class'     => 'icon',
        );
        $params = array_merge($defaults, $params);
        
        if (substr($params['image'], 0, 5) == "icon-")
        {
            $params['class'] .= ' '.$params['image'];
            unset($params['type'], $params['image']);
            return $this->iconComposeTag('i', $params);
        }
        else if ($params['type'] == "png")
        {
            $params['class'] .= ' ui-silk ui-silk-'.str_replace('_', '-', $params['image']);
            unset($params['type'], $params['image']);
            
            return $this->iconComposeTag('span', $params);
        }
        else
        {
            $icon_name = str_replace('.png', '', $params['image']).'.png';
            $params['src'] = \App\Url::content('common/icons/'.$params['type'].'/'.$icon_name);
            unset($params['size'], $params['image'], $params['type']);
            
            return $this->iconComposeTag('img', $params, '');
        }
    }
    
    public function iconComposeTag($tag_type = 'span', $params, $tag_contents = '')
    {
        $tag_string = array();
        foreach((array)$params as $key => $val)
        {
            if (!empty($val))
                $tag_string[] = $key.'="'.htmlspecialchars(trim($val)).'"';
        }
        
        if ($tag_type == "img")
            return '<'.$tag_type.' '.implode(' ', $tag_string).'>';
        else
            return '<'.$tag_type.' '.implode(' ', $tag_string).'>'.$tag_contents.'</'.$tag_type.'>';
    }
}