<?php

namespace Baseapp\Extension;

/**
 * Uniqueness Validator
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class Uniqueness extends \Phalcon\Validation\Validator implements \Phalcon\Validation\ValidatorInterface
{

    /**
     * Executes the validation
     *
     * @package     base-app
     * @version     2.0
     *
     * @param object $validation Phalcon\Validation
     * @param string $field field name
     *
     * @return boolean
     *
     * @throws \Phalcon\Validation\Exception
     */
    public function validate($validation, $field)
    {
        $value = $validation->getValue($field);
        $model = $this->getOption("model");
        $attribute = $this->getOption("attribute");

        if (empty($model)) {
            throw new \Phalcon\Validation\Exception("Model must be set");
        }

        if (empty($attribute)) {
            $attribute = $field;
        }

        if ($except = $this->getOption('except')) {
            $number = $model::count(array($attribute . "=:value: AND " . $attribute . "!= :except:", "bind" => array("value" => $value, 'except' => $except)));
        } else {
            $number = $model::count(array($attribute . "=:value:", "bind" => array("value" => $value)));
        }

        if ($number) {
            $label = $this->getOption("label");

            if (empty($label)) {
                $label = $validation->getLabel($field);

                if (empty($label)) {
                    $label = $field;
                }
            }

            $message = $this->getOption("message");
            $replacePairs = array(":field" => $label);

            if (empty($message)) {
                $message = $validation->getDefaultMessage("Uniqueness");
            }

            $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "Uniqueness"));
            return false;
        }
        return true;
    }

}
