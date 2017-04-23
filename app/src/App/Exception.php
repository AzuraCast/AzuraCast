<?php
namespace App;

class Exception extends \Exception
{
    protected $_extra_data = [];

    public function addExtraData($legend, $data)
    {
        if (is_array($data)) {
            $this->_extra_data[$legend] = $data;
        }
    }

    public function getExtraData()
    {
        return $this->_extra_data;
    }
}