<?php

namespace App\Form;

class Form extends \AzuraForms\Form
{
    /**
     * @inheritdoc
     */
    public function __construct(array $options = [], ?array $defaults = null)
    {
        array_unshift($this->field_namespaces, '\\App\\Form\\Field');

        $this->field_name_conversions['playlisttime'] = 'PlaylistTime';

        parent::__construct($options, $defaults);
    }

    /**
     * Render the entire form including submit button, errors, form tags etc.
     *
     * @return string
     */
    public function render(): string
    {
        $output = $this->openForm();

        if ($this->hasErrors()) {
            foreach($this->errors as $error) {
                $output .= '<div class="alert alert-danger" role="alert">'.$error.'</div>';
            }
        }

        foreach($this->options['groups'] as $fieldset_id => $fieldset) {
            $hide_fieldset = (bool)($fieldset['hide_fieldset'] ?? false);

            if (!$hide_fieldset) {
                $output .= sprintf('<fieldset id="%s" class="%s">',
                    $fieldset_id,
                    $fieldset['class'] ?? ''
                );

                $output .= '<div class="row">';

                if (!empty($fieldset['legend'])) {
                    $output .= '<legend class="'.$fieldset['legend_class'].'"><div>'.$fieldset['legend'].'</div></legend>';

                    if (!empty($fieldset['description'])) {
                        $output .= '<p class="'.$fieldset['description_class'].'">'.$fieldset['description'].'</p>';
                    }
                }
            }

            foreach($fieldset['elements'] as $element_id => $element_info) {
                if (isset($this->fields[$element_id])) {
                    $field = $this->fields[$element_id];
                    $output .= $field->render($this->name);
                }
            }

            if (!$hide_fieldset) {
                $output .= '</div>';
                $output .= '</fieldset>';
            }
        }

        $output .= $this->renderHidden();

        $output .= $this->closeForm();
        return $output;
    }
}

