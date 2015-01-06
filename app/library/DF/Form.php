<?php
namespace DF;

class Form
{
    /**
     * @var \Phalcon\Forms\Form The underlying form.
     */
    protected $form;

    /**
     * @var array|void Configuration.
     */
    protected $options;

    public function __construct($options, \Phalcon\Forms\Form $form = null)
    {
        if ($form === null)
            $form = new \Phalcon\Forms\Form;

        $this->form = new $form;

        if ($options instanceof \Phalcon\Config)
            $options = $options->toArray();

        $this->options = $options;
        $this->_setUpFields();
    }

    /**
     * Return the active form underlying this class.
     *
     * @return \Phalcon\Forms\Form The form.
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Return the configuration options for this form.
     *
     * @return null|array Form options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    protected function _setUpFields()
    {
        if ($this->options['groups']) {
            foreach($this->options['groups'] as $group) {
                foreach((array)$group['elements'] as $element_key => $element_options)
                    $this->_setUpField($element_key, $element_options);
            }
        }

        foreach((array)$this->options['elements'] as $element_key => $element_options) {
            $this->_setUpField($element_key, $element_options);
        }
    }

    protected function _setUpField($field_key, $field_params)
    {
        $field_type = $field_params[0];
        $field_options = (array)$field_params[1];

        // Clean up array.
        if (isset($field_options['multiOptions'])) {
            $select_options = $field_options['multiOptions'];
            unset($field_options['multiOptions']);
        } else {
            $select_options = array();
        }

        if (isset($field_options['label'])) {
            $element_label = $field_options['label'];
            unset($field_options['label']);
        } else {
            $element_label = ucfirst($field_key);
        }

        if (isset($field_options['default'])) {
            $element_default = $field_options['default'];
            unset($field_options['default']);
        } else {
            $element_default = NULL;
        }

        $element_validators = array();

        if (isset($field_options['required'])) {
            if ($field_options['required']) {
                $element_validators[] = new \Phalcon\Validation\Validator\PresenceOf(array(
                    'message'   => 'This field is required.',
                ));
            }

            unset($field_options['required']);
        }

        if (isset($field_options['minLength'])) {
            $element_validators[] = new \Phalcon\Validation\Validator\StringLength(array(
                'min'       => $field_options['minLength'],
                'message'   => 'This field must be at least '.$field_options['minLength'].' characters.',
            ));

            unset($field_options['minLength']);
        }

        // Set up element object.
        switch($field_type)
        {
            case 'password':
                $element = new \Phalcon\Forms\Element\Password($field_key, $field_options);
                break;

            case 'select':
                $element = new \Phalcon\Forms\Element\Select($field_key, $select_options, $field_options);
                break;

            case 'checkbox':
            case 'multiCheckbox':
                $element = new \Phalcon\Forms\Element\Check($field_key, $select_options, $field_options);
                break;

            case 'radio':
                $element = new \Phalcon\Forms\Element\Radio($field_key, $select_options, $field_options);
                break;

            case 'textarea':
                $element = new \Phalcon\Forms\Element\TextArea($field_key, $field_options);
                break;

            case 'hidden':
                $element = new \Phalcon\Forms\Element\Hidden($field_key, $field_options);
                break;

            case 'file':
                $element = new \Phalcon\Forms\Element\File($field_key, $field_options);
                break;

            case 'date':
                $element = new \Phalcon\Forms\Element\Date($field_key, $field_options);
                break;

            case 'numeric':
                $element = new \Phalcon\Forms\Element\Numeric($field_key, $field_options);
                break;

            case 'submit':
                $field_options['value'] = $element_label;
                $element_label = NULL;

                $element = new \Phalcon\Forms\Element\Submit($field_key, $field_options);
                break;

            case 'text':
            default:
                $element = new \Phalcon\Forms\Element\Text($field_key, $field_options);
                break;
        }

        // Set element label and defaults.
        $element->setLabel($element_label);
        $element->setDefault($element_default);

        // Set up required or min-length validators.
        if ($element_validators)
            $element->addValidators($element_validators);

        $this->form->add($element);
        return $this->form;
    }

    /**
     * Render the entire form (or a specified field name).
     *
     * @param null $name The portion of the form to render (leave null for the entire form).
     * @return string The rendered form.
     */
    public function render($name = null)
    {
        if ($name !== null)
            return $this->_renderField($name);

        $form_defaults = array(
            'method'        => 'POST',
            'action'        => '',
            'class'         => 'form-stacked df-form',
        );

        $form_options = (array)$this->options;
        unset($form_options['elements'], $form_options['groups']);

        $form_options = array_merge($form_defaults, $form_options);

        $form_tag = '<form';
        foreach((array)$form_options as $option_key => $option_value) {
            $form_tag .= ' '.$option_key.'="'.$option_value.'"';
        }
        $form_tag .= '>';

        $return = '';
        $return .= $form_tag;

        if ($this->options['groups']) {
            foreach($this->options['groups'] as $group_id => $group_info) {
                $return .= '<fieldset id="'.$group_id.'">';

                if (!empty($group_info['legend']))
                    $return .= '<legend>'.$group_info['legend'].'</legend>';

                foreach($group_info['elements'] as $element_key => $element_info) {
                    $return .= $this->_renderField($element_key);
                }

                $return .= '</fieldset>';
            }
        }

        if (!empty($this->options['elements'])) {
            foreach($this->options['elements'] as $element_key => $element_info) {
                $return .= $this->_renderField($element_key);
            }
        }

        $return .= '</form>';
        return $return;
    }

    protected function _renderField($name)
    {
        $element = $this->form->get($name);

        $return = '<div class="clearfix control-group">';

        $label = $element->getLabel();
        if (!empty($label))
            $return .= '<label for="'.$element->getName().'">'.$label.':</label>';

        //Get any generated messages for the current element
        $messages = $this->form->getMessagesFor($element->getName());

        if (count($messages)) {
            foreach ($messages as $message) {
                $return .= '<span class="help-block error">'.$message.'</span>';
            }
        }

        $return .= $this->form->render($name);

        $return .= '</div>';
        return $return;
    }

    public function renderView()
    {

    }
    public function renderMessage()
    {
        return $this->renderView();
    }

    public function isValid($submitted_data = null)
    {
        if ($submitted_data === null)
            $submitted_data = $_POST;

        return $this->form->isValid($submitted_data);
    }

    public function getValues($submitted_data = null)
    {
        if ($submitted_data === null)
            $submitted_data = $_POST;

        $values_obj = new \ArrayObject(array(), \ArrayObject::ARRAY_AS_PROPS);
        $this->form->bind($submitted_data, $values_obj);

        return $values_obj->getArrayCopy();
    }

}