<?php
namespace App\Forms\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;

// \Phalcon\Validation\Validator\InclusionIn

class SelectOptionsValidator extends Validator implements ValidatorInterface
{
    public function validate(\Phalcon\Validation $validation, $field)
    {
        $value = $validation->getValue($field);

        if (!$this->isSetOption("domain"))
            throw new \App\Exception("The option 'domain' is required for this validator");

        $domain = array();
        $domain_raw = (array)$this->getOption('domain');

        foreach($domain_raw as $domain_item)
            $domain[] = (string)$domain_item;


        // Requiring a value at all is handled by the "required" validator.
        if (empty($value))
            return true;

        if (is_array($value))
        {
            $is_valid = true;
            foreach($value as $value_item)
            {
                if (!in_array((string)$value_item, $domain, true))
                {
                    $is_valid = false;
                    break;
                }
            }
        }
        else
        {
            $is_valid = in_array((string)$value, $domain, true);
        }

        if (!$is_valid)
        {
            $message = $this->getOption('message');

            if (empty($message))
                $message = 'The field\'s value must be one of the listed items.';

            $message_obj = new Message(strtr($message, [":field" => $field, ":domain" => join(", ", $domain)]), $field, "SelectOptions");
            $validation->appendMessage($message_obj);
			return false;
        }

		return true;
    }
}