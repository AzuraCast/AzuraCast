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

    /**
     * @var array|void Field settings indexed by key.
     */
    protected $fields;

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

        // Save for later lookup.
        $this->fields[$field_key] = array('type' => $field_type) + $field_options;

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

        if (isset($field_options['required'])) {
            if ($field_options['required']) {
                $element_validators[] = new \Phalcon\Validation\Validator\PresenceOf(array(
                    'message'   => 'This field is required.',
                ));
            }

            unset($field_options['required']);
        }

        if (isset($field_options['validators']))
        {
            // Phalcon Bug:
            // Cannot instruct validators to ignore empty strings.
            // Only add other validators if "Required" validator is present.

            if (!empty($element_validators))
            {
                foreach ($field_options['validators'] as $validator)
                {
                    if (!is_string($validator))
                        continue;

                    switch (strtolower($validator)) {
                        case 'emailaddress':
                            $element_validators[] = new \Phalcon\Validation\Validator\Email(array(
                                'message' => 'This field must be a valid e-mail address.',
                            ));
                            break;
                    }
                }
            }

            unset($field_options['validators']);
        }

        if (isset($field_options['minLength'])) {
            $element_validators[] = new \Phalcon\Validation\Validator\StringLength(array(
                'min'       => $field_options['minLength'],
                'message'   => 'This field must be at least '.$field_options['minLength'].' characters.',
            ));

            unset($field_options['minLength']);
        }

        if (isset($field_options['belongsTo'])) {
            $field_name = $field_options['belongsTo'].'['.$field_key.']';
            $field_options['name'] = $field_name;
        }

        // Set up element object.
        switch(strtolower($field_type))
        {
            case 'password':
                $element = new \Phalcon\Forms\Element\Password($field_key, $field_options);
                break;

            case 'select':
                $element = new \Phalcon\Forms\Element\Select($field_key, $select_options, $field_options);
                break;

            case 'checkbox':
                $element = new \Phalcon\Forms\Element\Check($field_key, $field_options);
                break;

            case 'multicheckbox':
                $field_options['name'] = $field_key.'[]';
                $element = new \Phalcon\Forms\Element\Check($field_key, $field_options);
                break;

            case 'radio':
                $element = new \Phalcon\Forms\Element\Radio($field_key, $field_options);
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

            case 'unixdate':
                $element = new \DF\Forms\Element\UnixDate($field_key, $field_options);
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
                $element = new \DF\Forms\Element\Markup($field_key, $field_options['markup'], $field_options);
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
            return $this->_renderField($name, array());

        $form_defaults = array(
            'method'        => 'POST',
            'action'        => \DF\Url::current(),
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
                if (!empty($group_info['legend'])) {
                    $return .= '<fieldset id="' . $group_id . '">';
                    $return .= '<legend>' . $group_info['legend'] . '</legend>';
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

        $element = $this->form->get($name);

        $return = '<div class="clearfix control-group">';

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

            $return .= '<label for="' . $element->getName() . '" '.(($is_required) ? 'class="required"' : '').'>' . $label . (($is_required) ? '<span style="color: #FF0000;">*</span>' : '') . ':</label>';
        }

        if (!empty($field_options['description']))
            $return .= '<span class="help-block">'.$field_options['description'].'</span>';

        //Get any generated messages for the current element
        $messages = $this->form->getMessagesFor($element->getName());

        if (count($messages)) {
            foreach ($messages as $message) {
                $return .= '<span class="help-block error">'.$message.'</span>';
            }
        }

        switch($field_type)
        {
            case 'submit':
                return $this->form->render($name);
                break;

            case 'multiCheckbox':
            case 'radio':
                $return .= '<ul class="inputs-list inline">';

                $list_items = array();
                $default = $element->getDefault();

                foreach($field_options['multiOptions'] as $option_value => $option_label) {

                    // Force a "default" value.
                    if (is_array($default) && in_array($option_value, $default))
                        $element->setDefault($option_value);

                    $list_items[] = '<li><label>' . $this->form->render($name, array('value' => $option_value)) . ' <span>' . $option_label . '</span></label></li>';
                }

                $return .= implode('<br>', $list_items);
                $return .= '</ul>';
                break;

            default:
                $return .= $this->form->render($name);
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

        $element = $this->form->get($name);

        $return = '';

        switch($field_type)
        {
            case 'markup':
            case 'submit':
                return '';
                break;

            case 'multiCheckbox':
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
                $files = (array)$element->getValue();

                $return .= '<dd>';
                $i = 1;
                foreach($files as $file)
                {
                    $file_url = \DF\Url::content($file);
                    $return .= '<div>#'.$i.': <a href="'.$file_url.'" target="_blank">Download File</a></div>';

                    $i++;
                }
                $return .= '</dd>';
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

        // Process submitted data recursively.
        $return_data = $this->_getValues($submitted_data);

        // Create a "blank" array to merge against the resulting data for
        $elements = $this->form->getElements();
        $null_fill = array();

        foreach($elements as $field_key => $element)
        {
            if (isset($this->fields[$field_key]['belongsTo']))
            {
                $field_belongs_to = $this->fields[$field_key]['belongsTo'];
                $null_fill[$field_belongs_to][$field_key] = NULL;
            }
            else
            {
                $null_fill[$field_key] = NULL;
            }
        }

        // Fill the result array with any null values.
        $return_data = Utilities::array_merge_recursive_distinct($null_fill, $return_data);

        return $return_data;
    }

    protected function _getValues($submitted_data)
    {
        $return_data = array();

        foreach((array)$submitted_data as $key => $val)
        {
            if ($this->form->has($key))
                $return_data[$key] = $this->_getFieldValue($key, $val);
            elseif (is_array($val))
                $return_data[$key] = self::getValues($val);
            else
                continue;
        }

        return $return_data;
    }

    protected function _getFieldValue($field_key, $post_value)
    {
        $element = $this->form->get($field_key);

        if (method_exists($element, 'processValue'))
            return $element->processValue($post_value);
        else
            return $post_value;
    }

    public function setDefaults($default_values, $belongs_to = NULL)
    {
        foreach((array)$default_values as $field_key => $default_value)
        {
            if ($this->form->has($field_key))
            {
                // Validate that field corresponds to proper sub-array.
                if ($belongs_to)
                {
                    $field_belongs_to = $this->fields[$field_key]['belongsTo'];
                    if (strcmp($field_belongs_to, $belongs_to) == 0)
                        $set_field = true;
                    else
                        $set_field = false;
                }
                else
                {
                    $set_field = true;
                }

                if ($set_field)
                {
                    $element = $this->form->get($field_key);
                    $element->setDefault($default_value);
                }
            }
            else if (is_array($default_value))
            {
                // Only allow one level of "recursion" on BelongsTo.
                if (!$belongs_to)
                    $this->setDefaults($default_value, $field_key);
                else
                    continue;
            }
        }
    }

    public function populate($values)
    {
        $this->setDefaults($values);
    }

    /**
     * File upload processing
     */
    public function processFiles($destination_folder, $file_name_prefix = '', \Phalcon\Http\Request $request = null)
    {
        if ($request === null) {
            $di = \Phalcon\Di::getDefault();
            $request = $di->get('request');
        }

        if (!$request->hasFiles())
            return array();

        $return_fields = array();

        // Check for upload directory.
        $base_dir = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$destination_folder;

        if (!file_exists($base_dir))
            @mkdir($base_dir);

        // Loop through all uploaded files.
        $all_uploaded_files = $request->getUploadedFiles();

        foreach($all_uploaded_files as $file)
        {
            // Validate that this form contains a field with this name.
            $element_key = $file->getKey();
            if (!$this->form->has($element_key))
                continue;

            $element = $this->form->get($element_key);
            if (!($element instanceof \Phalcon\Forms\Element\File))
                continue;

            // Prepare array.
            if (isset($return_fields[$element_key])) {
                $i = count($return_fields[$element_key]) + 1;
            } else {
                $return_fields[$element_key] = array();
                $i = 1;
            }

            // Sanitize file name and generate new name.
            $element_name_clean = preg_replace('#[^a-zA-Z0-9\_]#', '', $element_key);

            $new_file_name = ($file_name_prefix) ? $file_name_prefix.'_' : '';
            $new_file_name .= date('Ymd_His').'_'.mt_rand(100, 999).'_'.$element_name_clean.'_'.$i.'.'.File::getFileExtension($file->getName());

            $new_file_path_short = $destination_folder.DIRECTORY_SEPARATOR.$new_file_name;
            $new_file_path_full = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_file_path_short;

            if (!is_writable(dirname($new_file_path_full)))
                throw new \DF\Exception('New directory not writable.');

            $file->moveTo($new_file_path_full);

            $return_fields[$element_key][$i] = $new_file_path_short;
        }

        return $return_fields;
    }

}