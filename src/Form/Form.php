<?php

declare(strict_types=1);

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

    /**
     * @return string[]
     */
    protected function getFormAttributes(): array
    {
        $attrs = parent::getFormAttributes();

        if (!empty($this->options['tabs'])) {
            $attrs['novalidate'] = 'novalidate';
        }

        return $attrs;
    }
}
