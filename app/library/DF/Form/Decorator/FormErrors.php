<?php
namespace DF\Form\Decorator;

class FormErrors extends \Zend_Form_Decorator_FormErrors
{
    public function render($content)
    {
        $errors = parent::render('');
        
        if (empty($errors))
            return $content;
        
        $markup = '<div class="alert block-message alert-error"><p>The form could not be submitted because the following errors occurred:</p>'.$errors.'</div>';
        
        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $this->getSeparator() . $markup;
            case self::PREPEND:
                return $markup . $this->getSeparator() . $content;
        }
    }
}
