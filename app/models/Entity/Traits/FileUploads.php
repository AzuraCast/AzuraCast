<?php
namespace Entity\Traits;

trait FileUploads
{
    /**
     * Process a file for uploading and delete any existing file that it replaces.
     * Returns true if file value changed, false if not.
     *
     * @param $field_name
     * @param $new_value
     * @return bool
     */
    protected function _processFile($field_name, $new_value)
    {
        if ($new_value)
        {
            if ($this->$field_name && $this->$field_name != $new_value)
                \PVL\Service\AmazonS3::delete($this->$field_name);

            $local_path = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.$new_value;
            \PVL\Service\AmazonS3::upload($local_path, $new_value);

            $this->$field_name = $new_value;
            return true;
        }

        return false;
    }

    /**
     * Process a new file for uploading, and crop it to fit specified dimensions.
     *
     * @param $field_name
     * @param $new_value
     * @param $width
     * @param $height
     * @return bool
     * @throws \DF\Exception
     */
    protected function _processAndCropImage($field_name, $new_value, $width, $height)
    {
        if (!$new_value)
            return false;

        $local_path = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.$new_value;
        \DF\Image::resizeImage($local_path, $local_path, $width, $height);

        return $this->_processFile($local_path, $new_value);
    }

    /**
     * Delete a file, if it exists, with the specified field name.
     *
     * @param $field_name
     */
    protected function _deleteFile($field_name)
    {
        if ($this->$field_name)
        {
            $value = $this->$field_name;
            \PVL\Service\AmazonS3::delete($value);
        }
    }

    /**
     * Detect
     *
     * @param $field_name
     * @param null $default_value
     * @return null
     */
    protected function _getFileValueOrDefault($field_name, $default_value=null)
    {
        if ($this->$field_name)
        {
            $value = $this->$field_name;

            $path = \PVL\Service\AmazonS3::path($value);
            if (file_exists($path))
                return $value;
        }

        return $default_value;
    }
}