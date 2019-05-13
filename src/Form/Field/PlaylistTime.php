<?php
namespace App\Form\Field;

use AzuraForms;
use AzuraForms\Field\Time;

class PlaylistTime extends Time
{
    public function __construct(AzuraForms\Form $form, $element_name, array $config = [], $group = null)
    {
        parent::__construct($form, $element_name, $config, $group);

        $this->attributes['pattern'] = '[0-9]{2}:[0-9]{2}';
        $this->attributes['placeholder'] = '13:45';
    }

    public function getValue()
    {
        if (empty($this->value)) {
            return null;
        }

        [$hours, $minutes] = explode(':', $this->value);
        return ((int)$hours * 100) + (int)$minutes;
    }

}
