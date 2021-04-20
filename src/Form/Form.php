<?php

namespace App\Form;

class Form extends \AzuraForms\Form
{
    public function __construct(array $options = [], ?array $defaults = null)
    {
        array_unshift($this->field_namespaces, '\\App\\Form\\Field');

        $this->field_name_conversions['playlisttime'] = 'PlaylistTime';

        parent::__construct($options, $defaults);
    }

    protected function addCsrfField(): void
    {
        $this->addField(
            self::CSRF_FIELD_NAME,
            Field\Csrf::class,
            [
                'csrf_key' => $this->name,
            ]
        );
    }

    public function openForm(): string
    {
        $attrs = [
            'id' => $this->name,
            'method' => $this->method,
            'action' => $this->action,
            'class' => 'form ' . ($this->options['class'] ?? ''),
            'accept-charset' => 'UTF-8',
        ];

        foreach ($this->fields as $field) {
            if ($field instanceof Field\File) {
                $attrs['enctype'] = 'multipart/form-data';
                break;
            }
        }

        if (!empty($this->options['tabs'])) {
            $attrs['novalidate'] = 'novalidate';
        }

        $attrsAsHtml = [];
        foreach ($attrs as $key => $val) {
            $attrsAsHtml[] = $key . '="' . $val . '"';
        }

        return '<form ' . implode(' ', $attrsAsHtml) . '>';
    }
}
