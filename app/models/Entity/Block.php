<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Block
 *
 * @Table(name="block")
 * @Entity
 */
class Block extends \DF\Doctrine\Entity
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="name", type="string", length=255, nullable=true) */
    protected $name;

    /** @Column(name="url", type="string", length=255, nullable=true) */
    protected $url;

    /** @Column(name="title", type="string", length=255, nullable=true) */
    protected $title;

    /** @Column(name="content", type="text", nullable=true) */
    protected $content;
    
    /**
     * Static Functions
     */

    public static function render($block_name, $block_vars = array(), $show_title = FALSE)
    {
        if ($block_name instanceof self)
            $block = $block_name;
        else
            $block = self::getRepository()->findOneByName($block_name);

        if (!($block instanceof self))
            return NULL;
        
        $content = '';
        
        if ($block->title && $show_title)
            $content .= '<h2 class="title">'.$block->title.'</h2>';
        
        $block_vars = $block_vars + array(
            'request'               => $_REQUEST,
            'get'                   => $_GET,
            'post'                  => $_POST,
            'server'                => $_SERVER,
        );

        $content .= $block->content;
        $content = self::contentReplace($content, $block_vars);
        
        return $content;
    }
    
    public static function contentReplace($content, $vars, $base='')
    {
        foreach((array)$vars as $var_key => $var_value)
        {
            if (is_array($var_value))
            {
                $content = self::contentReplace($content, $var_value, $var_key.'.');
            }
            else if ($var_value instanceof \DF\Config\Item)
            {
                $var_value = $var_value->toArray();
                $content = self::contentReplace($content, $var_value, $var_key.'.');
            }
            else if (!is_object($var_value))
            {
                $replace_key = '#'.$base.$var_key.'#';
                $content = str_replace($replace_key, $var_value, $content);
            }
        }
            
        return $content;
    }
}