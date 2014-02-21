<?php
namespace DF\Form\Decorator;
class FormFileEdit extends \Zend_Form_Decorator_File implements \Zend_Form_Decorator_Marker_File_Interface
{
    public function render($content)
    {
        $form_field = parent::render($content);
        
        $element = $this->getElement();
        $value = $element->getOriginalValue();
		
		if (defined('DF_UPLOAD_URL') && DF_UPLOAD_URL)
			$url_base = DF_UPLOAD_URL;
		else
			$url_base = \DF\Url::content();
        
        if ($value)
        {
            $form_field .= '</dd><dd class="warning"><b>Note:</b> You have already uploaded the file(s) listed below. To keep these files, leave this field blank.';
            
            if (!is_array($value))
                $value = array($value);
            
            $i = 0;
            foreach($value as $existing_file)
            {
                $i++;
                
                $file_url = $url_base.'/'.$existing_file;
				$form_field .= '<div>#'.$i.': <a href="'.$file_url.'" target="_blank">Download File</a></div>';
            }
            
            $form_field .= '</dd>';
        }
        
        return $form_field;
    }
}
