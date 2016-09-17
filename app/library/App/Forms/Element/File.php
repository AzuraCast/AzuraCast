<?php
namespace App\Forms\Element;

class File extends \Phalcon\Forms\Element\File
{
    public function renderView()
    {
        if (empty($this->_previous_value))
            return '';

        $file_rows = array();
        $di = $GLOBALS['di'];

        foreach((array)$this->_previous_value as $file)
        {
            $file_url = $di['url']->upload($file);
            $file_rows[] = '<a href="' . $file_url . '" target="_blank">' . $file . '</a>';
        }

        return '<ol type="1"><li>'.implode('</li><li>', $file_rows).'</li></ol>';
    }

    public function getValue()
    {
        return NULL;
    }
}