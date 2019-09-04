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
}

