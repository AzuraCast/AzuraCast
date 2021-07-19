<?php

declare(strict_types=1);

namespace App\Form\Field;

class File extends \AzuraForms\Field\File
{
    public function configure(array $config = []): void
    {
        parent::configure($config);

        $this->options['button_text'] = $this->attributes['button_text'] ?? __('Select File');
        $this->options['button_icon'] = $this->attributes['button_icon'] ?? null;
    }

    public function getField($form_name): ?string
    {
        [, $class] = $this->_attributeString();

        $button_text = $this->options['button_text'];

        if ($this->options['button_icon'] !== null) {
            $button_text .= sprintf(
                ' <i class="material-icons" aria-hidden="true">%1$s</i>',
                $this->options['button_icon']
            );
        }

        // phpcs:disable Generic.Files.LineLength
        $output = '<button name="%1$s_button" id="%2$s_%1$s_button" class="file-upload btn btn-primary btn-block text-center %3$s" type="button">';
        $output .= '%4$s';
        $output .= '</button>';
        $output .= '<small class="file-name"></small>';
        $output .= '<input type="file" name="%1$s" id="%2$s_%1$s" style="visibility: hidden; position: absolute; left: -9999px;">';
        // phpcs:enable

        return sprintf(
            $output,
            $this->getFullName(),
            $form_name,
            $class,
            $button_text
        );
    }
}
