<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
{
    public function log($message, $class = 'info')
    {
        if (!empty(trim($message)))
            parent::log(str_pad('Radio Backend: ', 20, ' ', STR_PAD_RIGHT).$message, $class);
    }
}