<?php

namespace DF;
class Form extends \Zend_Form
{
    /**
     * Custom override of the Zend_Form constructor
     */
    public function __construct($options = null)
    {
        $this->getPluginLoader(\Zend_Form::DECORATOR)->addPrefixPath('\DF\Form\Decorator\\', 'DF/Form/Decorator');
        $this->getPluginLoader(\Zend_Form::ELEMENT)->addPrefixPath('\DF\Form\Element\\', 'DF/Form/Element');
        
        $this->addElementPrefixPath('\DF\Validate\\', 'DF/Validate', \Zend_Form_Element::VALIDATE);
        $this->addElementPrefixPath('\DF\Filter\\', 'DF/Filter', \Zend_Form_Element::FILTER);
        
        $this->setElementFilters(array('StringTrim'));
        
        if ($options instanceof \Zend_Config)
            $options = $options->toArray();
        
        if (is_array($options) && isset($options['groups']))
        {
            foreach($options['groups'] as $group_key => $group)
            {
                // Special handling for items named "submit".
                if ($group_key == "submit")
                    $group_key = "submit_grp";
                
                $group_elements = (array)$group['elements'];
                unset($group['elements']);
                
                $options['displayGroups'][$group_key] = array(
                    'elements'      => array(),
                    'options'       => $group,
                );
                
                foreach($group_elements as $element_key => $element_info)
                {
                    $options['displayGroups'][$group_key]['elements'][] = $element_key;
                    $options['elements'][$element_key] = $element_info;
                }
            }
            
            unset($options['groups']);
        }
        
        // Check for default value.
        $defaults = array();
        foreach((array)$options['elements'] as $element_name => $element_info)
        {
            if (isset($element_info[1]['default']))
            {
                $defaults[$element_name] = $element_info[1]['default'];
                unset($options['elements'][$element_name][1]['default']);
            }
        }
        
        parent::__construct($options);
        $this->setDefaults($defaults);
    }
    
    public function isSubForm()
    {
        return FALSE;
    }
    
    public function setDefault($name, $value)
    {
        $name = (string) $name;
        if ($element = $this->getElement($name))
        {
            $element->setAttrib('df_raw_value', $value);
        }
        
        return parent::setDefault($name, $value);
    }
    protected function _dissolveArrayValue($value, $arrayPath)
    {
        return (array)parent::_dissolveArrayValue($value, $arrayPath);
    }
    
    public function clearAllDecorators()
    {
        $this->clearDecorators();
        
        foreach($this->getElements() as $element)
            $element->clearDecorators();
        
        foreach($this->getDisplayGroups() as $group)
            $group->clearDecorators();
        
        foreach($this->getSubForms() as $form)
        {
            if ($form instanceof self)
                $form->clearAllDecorators();
            else
                $form->clearDecorators();
        }

        return $this;
    }
    
    protected function preRender(\Zend_View_Interface &$view = null)
    {
        foreach($this->getElements() as $element)
        {
            $element->setDecorators(array(

                array(
                    'SpanFormErrors',
                    array(
                        'class' => 'help-block error',
                        'escape' => FALSE,
                        'placement' => \Zend_Form_Decorator_Abstract::PREPEND,
                    ),
                ),
                
                array(
                    'Description',
                    array(
                        'tag' => 'span',
                        'class' => 'help-block '.$errors,
                        'escape' => FALSE,
                        'placement' => \Zend_Form_Decorator_Abstract::PREPEND,
                    )
                ),
                
            ));
                
            if ($element instanceof \Zend_Form_Element_File)
            {
                $element->addDecorators(array(
                    array(
                        'FormFileEdit',
                        array(
                            'placement' => \Zend_Form_Decorator_Abstract::APPEND,
                        ),
                    ),
                ));
            }
            else
            {
                $element->addDecorators(array(
                    array(
                        'ViewHelper',
                        array(
                            'placement' => \Zend_Form_Decorator_Abstract::APPEND,
                        ),
                    ),
                ));
            }
            
            if (!($element instanceof \Zend_Form_Element_Button || $element instanceof \Zend_Form_Element_Submit))
            {
                $element->addDecorators(array(
                    array(
                        'Label',
                        array(
                            'escape' => FALSE,
                            'optionalSuffix' => ':',
                            'requiredSuffix' => '<span style="color: #FF0000;">*</span>:',
                        ),
                    ),
                    array(
                        'HtmlTag', 
                        array(
                            'tag' => 'div', 
                            'class' => 'clearfix control-group',
                        ),
                    ),
                ));
            }
            
            if( $element instanceOf \Zend_Form_Element_Hidden )
            {
                $element->setDecorators(array(
                    'ViewHelper',
                ));
            }
        }
        
        $subform_decorators = array(
            array(
                'Description',
                array(
                    'tag' => 'span',
                    'class' => 'help-block in-fieldset',
                    'escape' => FALSE,
                )
            ),
            array(
                'FormElements',
                array(
                    'tag' => '',
                )
            ),
        );
        $group_decorators = array_merge($subform_decorators, array(
            array('Fieldset'),
        ));
        
        if (!$this->isSubForm())
        {
            $this->setDecorators(array(
                array('FormErrors'),
                array('FormElements'),
                array('Form', array(
                    'class' => 'form-stacked df-form',
                )),
            ));
        }
        
        $this->setDisplayGroupDecorators($group_decorators);
        $this->setSubFormDecorators($subform_decorators);
    }

    public function render(\Zend_View_Interface $view = null)
    {
        $view_mode = $GLOBALS['df_form_mode'];
        
        if ($view_mode == "view" || $view_mode == "message")
            $this->preRenderView($view);
        else
            $this->preRender($view);
        
        return parent::render($view);
    }
    
    public function renderSpecial(\Zend_View_Interface $view = null, $view_mode = 'edit')
    {
        $GLOBALS['df_form_mode'] = $view_mode;
        $return_value = $this->render($view);
        $GLOBALS['df_form_mode'] = NULL;
        
        return $return_value;
    }
    public function renderView(\Zend_View_Interface $view = null)
    {
        return $this->renderSpecial($view, 'view');
    }
    public function renderMessage(\Zend_View_Interface $view = null)
    {
        return $this->renderSpecial($view, 'message');
    }
    
    /**
     * Read-only view
     */
    
    public function preRenderView(\Zend_View_Interface $view = null)
    {
        foreach($this->getElements() as $element)
        {
            $element->setDecorators(array(
                'FormView',
            ));
            
            if ($element instanceof \Zend_Form_Element_Button ||
                $element instanceof \Zend_Form_Element_Submit || 
                $element instanceof \Zend_Form_Element_Hidden || 
                $element instanceof Form\Element\Markup)
            {
                // Don't show these types of elements in this view
                $element->clearDecorators();
            }
            else if ($element instanceof \Zend_Form_Element_File)
            {
                // Add a fake "Form File View" decorator, since Zend_Form_Element_File needs it.
                $element->addDecorators(array(
                    'FormFileView',
                ));
            }
        }
        
        $group_decorators = array(
            array('FormElements'),
            array('FormView'),
        );
        
        $this->setDecorators($group_decorators);
        $this->setDisplayGroupDecorators($group_decorators);
        $this->setSubFormDecorators($group_decorators);
    }
    
    /**
     * File upload processing
     */
    public function processFiles($destination_folder, $file_name_prefix = '')
    {
        $return_fields = array();
        
        // Check for upload directory.
        $base_dir = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$destination_folder;
            
        if (!file_exists($base_dir))
            @mkdir($base_dir);
        
        foreach($this->getElements() as $element_name => $element)
        {
            if ($element instanceof \Zend_Form_Element_File)
            {
                $element_name_clean = preg_replace('#[^a-zA-Z0-9\_]#', '', $element_name);
                
                $file_names = (array)$element->getFileName();
                $original_files = (array)$element->getOriginalValue();

                if (!empty($file_names))
                {
                    $i = 1;
                    foreach($file_names as $file_path_original)
                    {
                        $new_file_name = ($file_name_prefix) ? $file_name_prefix.'_' : '';
                        $new_file_name .= date('Ymd_His').'_'.mt_rand(100, 999).'_'.$element_name_clean.'_'.$i.'.'.File::getFileExtension($file_path_original);
                        
                        $new_file_path_short = $destination_folder.DIRECTORY_SEPARATOR.$new_file_name;
                        $new_file_path_full = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_file_path_short;

                        if (!is_writable(dirname($new_file_path_full)))
                            throw new \DF\Exception('New directory not writable.');
                        
                        @rename($file_path_original, $new_file_path_full);
                        
                        $return_fields[$element_name][$i] = $new_file_path_short;
                        $i++;
                    }
                }
            }
        }
        
        return $return_fields;
    }
}