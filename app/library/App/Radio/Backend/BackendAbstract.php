<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
{
    public function log($message, $class = 'info')
    {
        if (!empty(trim($message)))
            parent::log('Radio Backend: '.$message, $class);
    }
}