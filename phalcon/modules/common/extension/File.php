<?php

namespace Baseapp\Extension;

/**
 * File Validator
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class File extends \Phalcon\Validation\Validator implements \Phalcon\Validation\ValidatorInterface
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
        $label = $this->getOption("label");

        if (empty($label)) {
            $label = $validation->getLabel($field);

            if (empty($label)) {
                $label = $field;
            }
        }

        // Upload is larger than PHP allowed size (post_max_size or upload_max_filesize)
        if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST) && empty($_FILES) && $_SERVER["CONTENT_LENGTH"] > 0 || isset($value["error"]) && $value["error"] === UPLOAD_ERR_INI_SIZE) {
            $message = $this->getOption("messageIniSize");
            $replacePairs = array(":field" => $label);

            if (empty($message)) {
                $message = $validation->getDefaultMessage("FileIniSize");
            }

            $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileIniSize"));
            return false;
        }

        if ($this->isSetOption("allowEmpty") && (empty($value) || isset($value["error"]) && $value["error"] === UPLOAD_ERR_NO_FILE)) {
            return true;
        }

        if (!isset($value["error"]) || !isset($value["tmp_name"]) || $value["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($value["tmp_name"])) {
            $message = $this->getOption("messageEmpty");
            $replacePairs = array(":field" => $label);

            if (empty($message)) {
                $message = $validation->getDefaultMessage("FileEmpty");
            }

            $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileEmpty"));
            return false;
        }

        if (!isset($value["name"]) || !isset($value["type"]) || !isset($value["size"])) {
            $message = $this->getOption("messageValid");
            $replacePairs = array(":field" => $label);

            if (empty($message)) {
                $message = $validation->getDefaultMessage("FileValid");
            }

            $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileValid"));
            return false;
        }

        if ($this->isSetOption("maxSize")) {
            $byteUnits = array("B" => 0, "K" => 10, "M" => 20, "G" => 30, "T" => 40, "KB" => 10, "MB" => 20, "GB" => 30, "TB" => 40);
            $maxSize = $this->getOption("maxSize");
            $matches = NULL;
            $unit = "B";
            preg_match("/^([0-9]+(?:\\.[0-9]+)?)(" . implode("|", array_keys($byteUnits)) . ")?$/Di", $maxSize, $matches);

            if (isset($matches[2])) {
                $unit = $matches[2];
            }
            $bytes = floatval($matches[1]) * pow(2, $byteUnits[$unit]);

            if (floatval($value["size"]) > floatval($bytes)) {
                $message = $this->getOption("messageSize");
                $replacePairs = array(":field" => $label, ":max" => $maxSize);

                if (empty($message)) {
                    $message = $validation->getDefaultMessage("FileSize");
                }

                $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileSize"));
                return false;
            }
        }

        if ($this->isSetOption("allowedTypes")) {
            $types = $this->getOption("allowedTypes");

            if (!is_array($types)) {
                throw new \Phalcon\Validation\Exception("Option 'allowedTypes' must be an array");
            }

            if (function_exists("finfo_open")) {
                $tmp = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($tmp, $value["tmp_name"]);
                finfo_close($tmp);
            } else {
                $mime = $value["type"];
            }

            if (!in_array($mime, $types)) {
                $message = $this->getOption("messageType");
                $replacePairs = array(":field" => $label, ":types" => join(", ", $types));

                if (empty($message)) {
                    $message = $validation->getDefaultMessage("FileType");
                }

                $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileType"));
                return false;
            }
        }

        if ($this->isSetOption("minResolution") || $this->isSetOption("maxResolution")) {
            list($width, $height) = getimagesize($value["tmp_name"]);

            if ($this->isSetOption("minResolution")) {
                $minResolution = explode("x", $this->getOption("minResolution"));
                $minWidth = $minResolution[0];
                $minHeight = $minResolution[1];
            } else {
                $minWidth = 1;
                $minHeight = 1;
            }

            if ($width < $minWidth || $height < $minHeight) {
                $message = $this->getOption("messageMinResolution");
                $replacePairs = array(":field" => $label, ":min" => $this->getOption("minResolution"));

                if (empty($message)) {
                    $message = $validation->getDefaultMessage("FileMinResolution");
                }

                $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileMinResolution"));
                return false;
            }

            if ($this->isSetOption("maxResolution")) {
                $maxResolution = explode("x", $this->getOption("maxResolution"));
                $maxWidth = $maxResolution[0];
                $maxHeight = $maxResolution[1];

                if ($width > $maxWidth || $height > $maxHeight) {
                    $message = $this->getOption("messageMaxResolution");
                    $replacePairs = array(":field" => $label, ":max" => $this->getOption("maxResolution"));

                    if (empty($message)) {
                        $message = $validation->getDefaultMessage("FileMaxResolution");
                    }

                    $validation->appendMessage(new \Phalcon\Validation\Message(strtr($message, $replacePairs), $field, "FileMaxResolution"));
                    return false;
                }
            }
        }

        return true;
    }

}
