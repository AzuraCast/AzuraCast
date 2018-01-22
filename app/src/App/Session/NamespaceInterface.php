<?php
namespace App\Session;

interface NamespaceInterface extends \ArrayAccess
{
    public function __set($name, $value);
    public function __get($name);
    public function __isset($name);
    public function __unset($name);
}