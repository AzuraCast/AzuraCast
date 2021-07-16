<?php

declare(strict_types=1);

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

        // Handle the "time code" format used by the database entity,
        // which is just the regular 24-hour time minus the ":".
        $this->filters[] = static function ($new_value) {
            // Don't use regular empty() check because 0 (which is 00:00, 12:00AM) is considered empty.
            if ('' !== $new_value && null !== $new_value && !str_contains($new_value, ':')) {
                $time_code = str_pad($new_value, 4, '0', STR_PAD_LEFT);
                return substr($time_code, 0, 2) . ':' . substr($time_code, 2);
            }
            return $new_value;
        };
    }

    /**
     */
    public function getValue(): string|int
    {
        if (empty($this->value)) {
            return '';
        }

        [$hours, $minutes] = explode(':', $this->value);
        return ((int)$hours * 100) + (int)$minutes;
    }
}
