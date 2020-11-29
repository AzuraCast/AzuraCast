<?php

namespace App\Form\Field;

use App\Exception\CsrfValidationException;
use App\Session;

class Csrf extends \AzuraForms\Field\Csrf
{
    protected function verifyCsrf(string $token): bool
    {
        try {
            $this->getCsrf()->verify($token, 'form_' . $this->options['csrf_key']);
        } catch (CsrfValidationException $e) {
            return false;
        }

        return true;
    }

    protected function generateCsrf(): string
    {
        return $this->getCsrf()->generate('form_' . $this->options['csrf_key']);
    }

    protected function getCsrf(): Session\Csrf
    {
        return Session\Csrf::getInstance();
    }
}
