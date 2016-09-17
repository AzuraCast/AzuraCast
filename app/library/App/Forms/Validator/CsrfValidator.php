<?php
namespace App\Forms\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;

class CsrfValidator extends Validator implements ValidatorInterface
{
    protected $_csrf;

    public function __construct()
    {
        $di = $GLOBALS['di'];
        $this->_csrf = $di['csrf'];
    }

    public function validate(\Phalcon\Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        $result = $this->_csrf->verify($value, 'form');

        if (!$result['is_valid'])
        {
            $validation->appendMessage(new Message($result['message'], $field, 'Csrf'));
            return false;
        }

        return true;
    }
}