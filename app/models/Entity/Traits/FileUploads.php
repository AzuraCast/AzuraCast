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
                @unlink($this->_getUploadedFilePath($this->$field_name));

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
        if ($this->_processFile($field_name, $new_value))
        {
            $new_path = $this->_getUploadedFilePath($new_value);
            \DF\Image::resizeImage($new_path, $new_path, $width, $height);

            return true;
        }
        return false;
    }

    /**
     * Get the full file path for an uploaded file.
     *
     * @param $field_value
     * @return string
     */
    protected function _getUploadedFilePath($field_value)
    {
        return DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$field_value;
    }
}