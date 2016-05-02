<?php
namespace DF\Forms\Element;

class File extends \Phalcon\Forms\Element\File
{
    protected $_previous_value;

    public function setPreviousValue($value)
    {
        $this->_previous_value = $value;
    }

    public function getPreviousValue()
    {
        return $this->_previous_value;
    }

    public function setDefault($value)
    {
        $this->setPreviousValue($value);
    }
    public function getDefault()
    {
        return NULL;
    }

    public function renderView()
    {
        if (empty($this->_previous_value))
            return '';

        $file_rows = array();

        foreach((array)$this->_previous_value as $file)
        {
            $file_url = \App\Url::upload($file);
            $file_rows[] = '<a href="' . $file_url . '" target="_blank">' . $file . '</a>';
        }

        return '<ol type="1"><li>'.implode('</li><li>', $file_rows).'</li></ol>';
    }

    public function render($attributes = null)
    {
        $return = '';

        if (!empty($this->_previous_value))
        {
            $return .= '<div>New uploads will replace your existing files. View existing files: ';

            $existing_files = array();
            foreach((array)$this->_previous_value as $file)
            {
                $file_url = \App\Url::upload($file);
                $existing_files[] = '<a href="' . $file_url . '" target="_blank">Download</a>';
            }

            $return .= implode(', ', $existing_files).'</div>';
        }

        $return .= parent::render($attributes);
        return $return;
    }
}