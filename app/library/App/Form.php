<?php
namespace App;

/**
 * A helper class that extends the Phalcon Form engine and allows flatfile configuration form management.
 *
 * Class Form
 * @package App
 */
class Form extends \Phalcon\Forms\Form
{
    /**
     * @var array|void Configuration.
     */
    protected $options;

    /**
     * @var array|void Field settings indexed by key.
     */
    protected $fields;

    /**
     * @var bool Whether the form has any current processing errors.
     */
    protected $has_errors = false;

    /**
     * Form constructor.
     * @param $options
     */
    public function __construct($options)
    {
        parent::__construct();

        if ($options instanceof \App\Config\Item)
            $options = $options->toArray();

        $this->options = $options;
        $this->_setUpFields();
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
        if (isset($this->options['groups'])) {
            foreach($this->options['groups'] as $group) {
                foreach((array)$group['elements'] as $element_key => $element_options)
                    $this->_setUpField($element_key, $element_options);
            }
        }

        if (isset($this->options['elements']))
        {
            foreach((array)$this->options['elements'] as $element_key => $element_options) {
                $this->_setUpField($element_key, $element_options);
            }
        }
    }

    protected function _setUpField($field_key, $field_params)
    {
        $js_validation = array();

        if (isset($field_params[0]))
            $field_type = strtolower($field_params[0]);
        else
            $field_type = 'text';

        if (isset($field_params[1]))
            $field_options = (array)$field_params[1];
        else
            $field_options = array();

        // Make all field options lower-case.
        $field_options_new = array();
        foreach($field_options as $field_option_key => $field_option_value)
            $field_options_new[strtolower($field_option_key)] = $field_option_value;

        $field_options = $field_options_new;

        // Handle rename of "multiOptions" to "options".
        if (isset($field_options['multioptions']))
        {
            $field_options['options'] = $field_options['multioptions'];
            unset($field_options['multioptions']);
        }

        // Save for later lookup.
        $this->fields[$field_key] = array('type' => $field_type) + $field_options;

        // Clean up array.
        if (isset($field_options['options']))
        {
            $select_options = $field_options['options'];
            unset($field_options['options']);
        }
        else
        {
            $select_options = array();
        }

        if (isset($field_options['label'])) {
            $element_label = $field_options['label'];
            unset($field_options['label']);
        } else {
            $element_label = NULL;
        }

        if (isset($field_options['default'])) {
            $element_default = $field_options['default'];
            unset($field_options['default']);
        } else {
            $element_default = NULL;
        }

        if (isset($field_options['description']))
            unset($field_options['description']);

        $element_validators = array();

        // Set up file validation options.
        if ($field_type == 'file')
        {
            if (!empty($field_options['maxsize']) || !empty($field_options['allowedtypes']))
            {
                $validate_params = array();

                if (!empty($field_options['maxsize']))
                {
                    $validate_params['maxsize'] = $field_options['maxsize'];

                    $js_validation['size'] = array('max-size' => $field_options['maxsize']);
                }

                if (!empty($field_options['allowedtypes']))
                {
                    $validate_params['allowedtypes'] = $field_options['allowedtypes'];
                    $field_options['accept'] = implode(', ', $field_options['allowedtypes']);
                }

                if (empty($field_options['required']) || !$field_options['required'])
                    $validate_params['allowEmpty'] = true;

                $element_validators[] = new \Phalcon\Validation\Validator\File($validate_params);

                unset($field_options['maxsize']);
                unset($field_options['allowedtypes']);
            }
        }

        if (isset($field_options['required'])) {
            if ($field_options['required']) {
                $element_validators[] = new \Phalcon\Validation\Validator\PresenceOf(array(
                    'message'   => 'This field is required.',
                    'cancelOnFail' => true,
                ));

                $js_validation['required'] = array();
            }

            unset($field_options['required']);
        }

        if (isset($field_options['validators']))
        {
            foreach ($field_options['validators'] as $validator)
            {
                if (!is_string($validator))
                    continue;

                switch (strtolower($validator)) {
                    case 'emailaddress':
                    case 'email':
                        $element_validators[] = new \Phalcon\Validation\Validator\Email(array(
                            'message' => 'This field must be a valid e-mail address.',
                            'allowEmpty' => true,
                        ));

                        $js_validation['email'] = array();
                    break;

                    case 'webaddress':
                    case 'url':
                        $element_validators[] = new \Phalcon\Validation\Validator\Url(array(
                            'message' => 'This field must be a valid web address.',
                            'allowEmpty' => true,
                        ));

                        $js_validation['url'] = array();
                    break;
                }
            }

            unset($field_options['validators']);
        }

        // TODO: Implement filters.
        if (isset($field_options['filters']))
            unset($field_options['filters']);

        // Minimum and Maximum Length Validation
        if (isset($field_options['minlength']) && isset($field_options['maxlength']))
        {
            $element_validators[] = new \Phalcon\Validation\Validator\StringLength(array(
                'min'       => $field_options['minlength'],
                'max'       => $field_options['maxlength'],
                'message'   => 'This field must be between '.$field_options['minlength'].' and '.$field_options['maxlength'].' characters.',
            ));

            $js_validation['length'] = array('length' => $field_options['minlength'].'-'.$field_options['maxlength']);

            unset($field_options['minlength'], $field_options['maxlength']);
        }
        else if (isset($field_options['minlength']))
        {
            $element_validators[] = new \Phalcon\Validation\Validator\StringLength(array(
                'min'       => $field_options['minlength'],
                'message'   => 'This field must be at least '.$field_options['minlength'].' characters.',
            ));

            $js_validation['length'] = array('length' => 'min'.$field_options['minlength']);

            unset($field_options['minlength']);
        }
        else if (isset($field_options['maxlength']))
        {
            $element_validators[] = new \Phalcon\Validation\Validator\StringLength(array(
                'max'       => $field_options['maxlength'],
                'message'   => 'This field must be less than '.$field_options['maxlength'].' characters.',
            ));

            $js_validation['length'] = array('length' => 'max'.$field_options['maxlength']);

            unset($field_options['maxlength']);
        }

        // Confirmation validation (with another field).
        if (isset($field_options['confirm']))
        {
            $element_validators[] = new \Phalcon\Validation\Validator\Confirmation(array(
                'with' => $field_options['confirm'],
            ));

            $js_validation['confirmation'] = array('confirm' => $field_options['confirm']);

            unset($field_options['confirm']);
        }

        // Set up JS validation.
        if (!empty($js_validation))
        {
            $field_options['data-validation'] = implode(' ', array_keys($js_validation));

            foreach($js_validation as $js_validation_item => $js_validation_options)
            {
                if (!empty($js_validation_options))
                {
                    foreach($js_validation_options as $validate_key => $validate_val)
                        $field_options['data-validation-'.$validate_key] = $validate_val;
                }
            }
        }

        // Remove internal classes not needed for render.
        unset($field_options['belongsto']);
        unset($field_options['layout']);

        // Set up element object.
        switch($field_type)
        {
            case 'password':
                $element = new \Phalcon\Forms\Element\Password($field_key, $field_options);
            break;

            case 'select':
                $element = new \Phalcon\Forms\Element\Select($field_key, $select_options, $field_options);

                $validator = new \App\Forms\Validator\SelectOptionsValidator(array(
                    'domain' => Utilities::array_keys_recursive($select_options),
                ));
                $element->addValidator($validator);
            break;

            case 'checkbox':
                $element = new \Phalcon\Forms\Element\Check($field_key, $field_options);
            break;

            case 'checkboxes':
            case 'multicheckbox':
                $element = new \App\Forms\Element\MultiCheckbox($field_key, $select_options, $field_options);

                $validator = new \App\Forms\Validator\SelectOptionsValidator(array(
                    'domain' => Utilities::array_keys_recursive($select_options),
                ));
                $element->addValidator($validator);
            break;

            case 'radio':
                $element = new \Phalcon\Forms\Element\Radio($field_key, $field_options);

                $validator = new \App\Forms\Validator\SelectOptionsValidator(array(
                    'domain' => Utilities::array_keys_recursive($select_options),
                ));
                $element->addValidator($validator);
            break;

            case 'textarea':
                $element = new \Phalcon\Forms\Element\TextArea($field_key, $field_options);
            break;

            case 'hidden':
                $element = new \Phalcon\Forms\Element\Hidden($field_key, $field_options);
            break;

            case 'file':
                $element = new \App\Forms\Element\File($field_key, $field_options);
            break;

            case 'image':
                $element = new \App\Forms\Element\Image($field_key, $field_options);
            break;

            case 'date':
                $element = new \Phalcon\Forms\Element\Date($field_key, $field_options);
            break;

            case 'unixdate':
                $element = new \App\Forms\Element\UnixDate($field_key, $field_options);
            break;

            case 'numeric':
                $element = new \Phalcon\Forms\Element\Numeric($field_key, $field_options);
            break;

            case 'submit':
                $field_options['value'] = $element_label;
                $element_label = NULL;

                $element = new \Phalcon\Forms\Element\Submit($field_key, $field_options);
            break;

            case 'markup':
                $element = new \App\Forms\Element\Markup($field_key, $field_options['markup'], $field_options);
            break;

            case 'captcha':
            case 'recaptcha':
                $element = new \App\Forms\Element\Recaptcha($field_key, $field_options);

                $validator = new \App\Forms\Validator\RecaptchaValidator;
                $element->addValidator($validator);
            break;

            case 'csrf':
                $element = new \App\Forms\Element\Csrf($field_key, $field_options);

                $validator = new \App\Forms\Validator\CsrfValidator;
                $element->addValidator($validator);
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

        $this->add($element);
        return $this;
    }

    /**
     * Render the entire form (or a specified field name).
     *
     * @param null $name The portion of the form to render (leave null for the entire form).
     * @return string The rendered form.
     */
    public function render($name = null, $attributes = null)
    {
        if ($name !== null)
            return $this->_renderField($name, array());

        $form_defaults = array(
            'method'        => 'POST',
            'action'        => '',
            'id'            => 'fa-form-'.substr(md5(mt_rand()), 0, 5),
            'class'         => 'form-stacked fa-form-engine fa-form',
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
                if (!empty($group_info['legend'])) {
                    $return .= '<fieldset id="' . $group_id . '">';
                    $return .= '<legend>' . $group_info['legend'] . '</legend>';

                    if ($group_info['description'])
                        $return .= '<p>'.$group_info['description'].'</p>';
                }

                foreach($group_info['elements'] as $element_key => $element_info) {
                    $return .= $this->_renderField($element_key, $element_info);
                }

                if (!empty($group_info['legend'])) {
                    $return .= '</fieldset>';
                }
            }
        }

        if (!empty($this->options['elements'])) {
            foreach($this->options['elements'] as $element_key => $element_info) {
                $return .= $this->_renderField($element_key, $element_info);
            }
        }

        $return .= '</form>';
        return $return;
    }

    protected function _renderField($name, $field_params)
    {
        $field_type = $field_params[0];
        $field_options = $field_params[1];

        $element = $this->get($name);

        $wrapper_class = 'clearfix control-group';
        if (isset($field_options['layout']))
            $wrapper_class .= ' '.implode(' ', (array)$field_options['layout']);

        $return = '<div class="'.$wrapper_class.'">';

        $label = $element->getLabel();
        if (!empty($label)) {
            // Check if field is required.
            $validators = $element->getValidators();
            $is_required = false;

            if (count($validators)) {
                foreach($validators as $v_obj) {
                    if ($v_obj instanceof \Phalcon\Validation\Validator\PresenceOf)
                        $is_required = true;
                }
            }

            $class = array('control-label');

            if ($is_required)
                $class[] = 'required';

            $return .= '<label for="' . $element->getName() . '" class="'.implode(' ', $class).'">' . $label . (($is_required) ? '<span class="required">*</span>' : '') . ':</label>';
        }

        if (!empty($field_options['description']))
            $return .= '<span class="help-block">'.$field_options['description'].'</span>';

        // Get any generated messages for the current element
        $messages = array();

        $form_messages = $this->getMessagesFor($element->getName());
        foreach($form_messages as $form_message)
            $messages[] = $form_message;

        $element_messages = $element->getMessages();
        foreach($element_messages as $element_message)
            $messages[] = $element_message;

        if (!empty($messages)) {
            foreach ($messages as $message) {
                $return .= '<span class="help-block form-error">'.$message.'</span>';
            }
        }

        switch(strtolower($field_type))
        {
            case 'submit':
                return parent::render($name);
            break;

            case 'checkboxes':
            case 'multicheckbox':
                $return .= '<ul class="inputs-list">';

                $checkboxes = $element->getCheckboxes();
                $list_items = array();

                foreach($checkboxes as $checkbox)
                {
                    $list_items[] = '<li>'.$checkbox.'</li>';
                }

                $return .= implode('', $list_items);
                $return .= '</ul>';
            break;

            case 'radio':
                $return .= '<ul class="inputs-list">';

                $list_items = array();
                $default = $element->getDefault();

                foreach($field_options['options'] as $option_value => $option_label) {

                    // Force a "default" value.
                    if (is_array($default) && in_array($option_value, $default))
                        $element->setDefault($option_value);

                    $list_items[] = '<li><label>' . parent::render($name, array('value' => $option_value)) . ' <span>' . $option_label . '</span></label></li>';
                }

                $return .= implode('', $list_items);
                $return .= '</ul>';
            break;

            case 'hidden':
            case 'csrf':
                $return = '';
                if (!empty($messages))
                {
                    foreach($messages as $message)
                        $return .= '<!-- Validator Message: '.$message.' -->';
                }

                return $return.parent::render($name);
            break;

            default:
                $return .= parent::render($name);
            break;
        }

        $return .= '</div>';

        return $return;
    }

    public function renderView()
    {
        $return = '';
        $return .= '<div class="form-view">';

        if ($this->options['groups']) {
            foreach($this->options['groups'] as $group_id => $group_info) {

                $elements_return = '';
                foreach($group_info['elements'] as $element_key => $element_info) {
                    $elements_return .= $this->_renderFieldView($element_key, $element_info);
                }

                $elements_return = trim($elements_return);

                // Hide empty fieldsets.
                if (empty($elements_return))
                    continue;

                if (!empty($group_info['legend']))
                    $return .= '<h3>' . $group_info['legend'] . '</h3>';

                $return .= '<dl>'.$elements_return.'</dl>';

                if (!empty($group_info['legend'])) {
                    $return .= '</fieldset>';
                }
            }
        }

        if (!empty($this->options['elements'])) {

            $elements_return = '';
            foreach($this->options['elements'] as $element_key => $element_info) {
                $elements_return .= $this->_renderFieldView($element_key, $element_info);
            }

            $elements_return = trim($elements_return);

            if (!empty($elements_return))
                $return .= '<dl>'.$elements_return.'</dl>';
        }

        $return .= '</div>';

        return $return;
    }
    public function renderMessage()
    {
        return $this->renderView();
    }

    protected function _renderFieldView($name, $field_params)
    {
        $field_type = $field_params[0];
        $field_options = $field_params[1];

        $element = $this->get($name);

        $return = '';

        switch(strtolower($field_type))
        {
            case 'markup':
            case 'submit':
                return '';
            break;

            case 'multicheckbox':
            case 'checkboxes':
            case 'radio':
                $options = $field_options['multiOptions'];
                $value = $element->getValue();

                if (is_array($value))
                {
                    $return .= '<dd><ul>';
                    foreach($value as $key)
                        $return .= '<li>'.$options[$key].'</li>';

                    $return .= '</ul></dd>';
                }
                else
                {
                    if (isset($options[$value]))
                        $return .= '<dd>'.$options[$value].'</dd>';
                }
            break;

            case 'file':
            case 'image':
                $file_list = $element->renderView();
                if ($file_list)
                    $return = '<dd>'.$file_list.'</dd>';
            break;

            default:
                $value = trim($element->getValue());

                if (!empty($value))
                    $return .= '<dd>'.$value.'</dd>';
            break;
        }

        // Only add label for non-empty elements.
        $label = $element->getLabel();
        if (!empty($return) && !empty($label))
            $return = '<dt>'.$label.':</dt>'.$return;

        return $return;
    }

    public function isValid($data = null, $entity = null)
    {
        $this->populate($data);

        $is_valid = parent::isValid($data, $entity);
        if (!$is_valid)
            $this->has_errors = true;

        return $is_valid;
    }

    public function getValues()
    {
        $elements = $this->getElements();
        $return_data = array();

        foreach($elements as $field_key => $element)
        {
            $value = $this->getValue($field_key);

            if (method_exists($element, 'processValue'))
                $value = $element->processValue($value);

            if (isset($this->fields[$field_key]['belongsto']))
            {
                $field_belongs_to = $this->fields[$field_key]['belongsto'];

                if (!isset($return_data[$field_belongs_to]))
                    $return_data[$field_belongs_to] = array();

                $return_data[$field_belongs_to][$field_key] = $value;
            }
            else
            {
                $return_data[$field_key] = $value;
            }
        }

        return $return_data;
    }

    public function setDefaults($default_values)
    {
        foreach((array)$default_values as $field_key => $default_value)
        {
            if ($this->has((string)$field_key))
            {
                $element = $this->get($field_key);
                $element->setDefault($default_value);
            }
        }

        return $this;
    }

    public function getDefaults()
    {
        $elements = $this->getElements();
        $return_data = array();

        foreach($elements as $field_key => $element)
        {
            $value = $element->getDefault();

            if (isset($this->fields[$field_key]['belongsto']))
            {
                $field_belongs_to = $this->fields[$field_key]['belongsto'];

                if (!isset($return_data[$field_belongs_to]))
                    $return_data[$field_belongs_to] = array();

                $return_data[$field_belongs_to][$field_key] = $value;
            }
            else
            {
                $return_data[$field_key] = $value;
            }
        }

        return $return_data;
    }

    public function populate($values)
    {
        return $this->setValues($values);
    }

    public function setValues($values)
    {
        if (empty($this->_data))
            $this->_data = array();

        foreach((array)$values as $field_key => $default_value)
        {
            if ($this->has((string)$field_key))
                $this->_data[$field_key] = $default_value;
        }

        return $this;
    }

    public function addError($field_name, $message)
    {
        $element = $this->get($field_name);

        if (!isset($this->_messages[$field_name]))
            $this->_messages[$field_name] = new \Phalcon\Validation\Message\Group();

        $this->_messages[$field_name]->appendMessage(new \Phalcon\Validation\Message($message, $field_name, 'error'));

        $this->has_errors = true;
    }

    public function hasErrors()
    {
        return $this->has_errors;
    }

    /**
     * Return all files associated with valid file input types in this form.
     *
     * @param \Phalcon\Http\Request|null $request
     * @return \Phalcon\Http\Request\File[]
     */
    public function getFiles(\Phalcon\Http\Request $request = null)
    {
        if ($request === null)
        {
            $di = \Phalcon\Di::getDefault();
            $request = $di->get('request');
        }

        if (!$request->hasFiles())
            return array();

        $return_fields = array();

        // Loop through all uploaded files.
        $all_uploaded_files = $request->getUploadedFiles();

        foreach ($all_uploaded_files as $file)
        {
            if (!$file->isUploadedFile())
                continue;

            // Validate that this form contains a field with this name.
            $element_key = $file->getKey();
            if (!$this->has($element_key))
                continue;

            $element = $this->get($element_key);
            if (!($element instanceof \Phalcon\Forms\Element\File))
                continue;

            // Prepare array.
            $i = 0;

            if (isset($return_fields[$element_key]))
                $i = count($return_fields[$element_key]);
            else
                $return_fields[$element_key] = array();

            $return_fields[$element_key][$i] = $file;
        }

        return $return_fields;
    }

}