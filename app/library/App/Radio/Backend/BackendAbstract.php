<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
{
    public function log($message, $class = 'info')
    {
        parent::log('Radio Backend: '.$message, $class);
    }
}