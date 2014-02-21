<?php
namespace DF\Form\Decorator;

class FormView extends \Zend_Form_Decorator_Form
{
    public function render($content)
    {
		$view_mode = $GLOBALS['df_form_mode'];
		$element = $this->getElement();
		
		// Special handling for subforms and forms themselves.
		if ($element instanceof \Zend_Form)
		{
			$elements = $element->getElements();
			$groups = $element->getDisplayGroups();
			$attribs = $element->getAttribs();
			$content = trim($content);
			
			// Wrap content in DL tag if not otherwise done.
			if (count($groups) == 0 && $content)
				$content = '<dl>'.$content.'</dl>';
			
			if ($view_mode == "message")
				return $content;
			else
				return '<div class="form-view">'.$content.'</div>';
		}
		
		// Special handling for formsets.
		if ($element instanceof \Zend_Form_DisplayGroup)
		{
			if (trim($content))
			{
				$attribs = $element->getAttribs();
				
				$return = '';
				if ($attribs['legend'])
					$return .= '<h3>'.$attribs['legend'].'</h3>';
				
				$return .= '<dl>'.trim($content).'</dl>';
				return $return;
			}
			
			return NULL;
		}
		
		if ($element->getIgnore() == TRUE)
			return NULL;
		
		$element_attribs = $element->getAttribs();
		$element_options = (isset($element_attribs['options'])) ? $element_attribs['options'] : array();
		$element_value = $element_attribs['df_raw_value'];
		
		if ($element_value === NULL)
			return NULL;
			
		$return_content = '';
		
		if ($element instanceof \Zend_Form_Element_File)
		{
			if (defined('DF_UPLOAD_URL') && DF_UPLOAD_URL)
				$url_base = DF_UPLOAD_URL;
			else
				$url_base = \DF\Url::content();
			
			$files = (array)$element_value;
			
			$i = 1;
			foreach($files as $file)
			{
				$file_url = $url_base.'/'.$file;
				$return_content .= '<div>#'.$i.': <a href="'.$file_url.'" target="_blank">Download File</a>';
				
				$i++;
			}
		}
		else if ($element instanceof \DF\Form\Element\UnixDate)
		{
			$return_content .= ($element_value != 0) ? date('F j, Y', $element_value) : '';
		}
		else if ($element instanceof \DF\Form\Element\UnixDateTime)
		{
			$return_content .= ($element_value != 0) ? date('F j, Y g:ia', $element_value) : '';
		}
		else if (is_array($element_value))
		{
			$return_content .= '<ul>';
			
			foreach($element_value as $element_value_item)
			{
				if (isset($element_options[$element_value_item]))
					$element_value_item = $element_options[$element_value_item];
				
				$return_content .= '<li>'.$element_value_item.'</li>';
			}
			
			$return_content .= '</ul>';
		}
		else
		{
			$validators = (array)$element->getValidators();
			$validator_classes = array_keys($validators);
			
			$filters = (array)$element->getFilters();
			$filter_classes = array_keys($filters);
			
			if ($element_value && in_array('Zend_Validate_EmailAddress', $validator_classes))
			{
				$element_value = '<a href="mailto:'.$element_value.'">'.$element_value.'</a>';
			}
			elseif ($element_value && in_array('DF\Filter\WebAddress', $filter_classes))
			{
				$element_value = '<a href="'.$element_value.'" target="_blank">'.$element_value.'</a>';
			}
			
			if ($element_options)
				$return_content .= nl2br($element_options[$element_value]);
			else
				$return_content .= nl2br($element_value);
		}
		
		if (trim($return_content))
		{
			$label_raw = $element->getLabel();
			
			if ($label_raw)
				$label = '<dt>'.$label_raw.':</dt>';
			else
				$label = '';
			
			return $label.'<dd>'.$return_content.'</dd>';
		}
    }
}
