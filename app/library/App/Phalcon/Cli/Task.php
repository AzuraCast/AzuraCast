<?php
namespace App\Phalcon\Cli;

class Task extends \Phalcon\CLI\Task
{
    protected function printLn($text)
    {
        echo $text.PHP_EOL;
    }
}