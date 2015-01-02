<?php
namespace DF\View\Helper;
class Tinymce extends \Zend_View_Helper_Abstract
{
    /**
     * Adds TinyMCE text editor support to the entire page, and supports using unique selectors to distinguish which textareas get styled.
     *
     * @param string selector The jQuery selector to use (i.e. textarea.tinymce, #item_1)
     */
    public function tinymce($selector = 'textarea')
    {
        if (!defined('DF_TINYMCE_INCLUDED'))
        {
            $this->view->headScript()->appendFile(\DF\Url::content('common/tinymce/jscripts/tiny_mce/jquery.tinymce.js'));
            define('DF_TINYMCE_INCLUDED', TRUE);
        }
        
        $inline_script = '
        $(function() {
            $(\''.$selector.'\').addClass("full-width full-height").tinymce({
                script_url: "'.\DF\Url::content('common/tinymce/jscripts/tiny_mce/tiny_mce.js').'",

                mode: "textareas",
                entity_encoding: "raw",
                theme: "advanced",
                plugins: "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

                theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
                theme_advanced_buttons2: "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
                theme_advanced_toolbar_location: "top",
                theme_advanced_toolbar_align: "left",
                theme_advanced_statusbar_location: "bottom",
                theme_advanced_resizing: true,
                convert_urls: false
            });
        });
        ';
        
        $this->view->headScript()->appendScript($inline_script);
    }
}