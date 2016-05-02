<?php
/**
 * Static class that facilitates the uploading, reading and deletion of files in a controlled directory.
 */

namespace App;
class File
{
    // Add a suffix to a file *before* its extension.
    public static function addSuffix($file_name, $suffix)
    {
        $file_parts = pathinfo($file_name);
        $new_file_name = $file_parts['filename'].$suffix.'.'.$file_parts['extension'];

        if ($file_parts['dirname'])
            return $file_parts['dirname'].DIRECTORY_SEPARATOR.$new_file_name;
        else
            return $new_file_name;
    }

    public static function getFileExtension($file_name)
    {
        // Significantly more performant than using pathinfo function.
        return substr($file_name, strrpos($file_name, '.')+1);
    }
    public static function getFilePath($file_name)
    {
        $file_name = trim($file_name);
        $file_name = ltrim($file_name, '/');
        $file_name = str_replace('/', DIRECTORY_SEPARATOR, $file_name);

        if (is_readable($file_name) || stristr($file_name, APP_UPLOAD_FOLDER) !== FALSE)
            return $file_name;
        else
            return APP_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.str_replace('..','',$file_name);
    }
    
    public static function getFileUrl($file_name)
    {
        $di = \Phalcon\Di::getDefault();
        $url = $di['url'];

        if (defined('APP_UPLOAD_URL'))
            return APP_UPLOAD_URL.'/'.$file_name;
        else
            return $url->content($file_name);
    }
    
    public static function isValidFile($uploaded_file, $allowed_extensions = NULL)
    {
        $is_valid_upload = (!empty($uploaded_file) && $uploaded_file['error'] == UPLOAD_ERR_OK);
        
        if (!is_null($allowed_extensions))
        {
            $file_extension = self::getFileExtension($uploaded_file['name']);
            $is_valid_extension = in_array($file_extension, $allowed_extensions);
        }
        else
        {
            $is_valid_extension = TRUE;
        }
        
        return $is_valid_upload && $is_valid_extension;
    }
    
    public static function moveUploadedFile($uploaded_file, $file_name = '')
    {
        if (!self::isValidFile($uploaded_file))
        {
            switch($uploaded_file['error'])
            {
                case UPLOAD_ERR_INI_SIZE:
                    throw new \App\Exception\DisplayOnly('File Upload Error: The file you are attempting to upload is larger than allowed (upload_max_filesize).');
                    break;
                
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \App\Exception\DisplayOnly('File Upload Error: The file you are attempting to upload is larger than allowed (MAX_FILE_SIZE).');
                    break;
                    
                case UPLOAD_ERR_PARTIAL:
                    throw new \App\Exception\DisplayOnly('File Upload Error: The file you are attempting to upload was only partially uploaded.');
                    break;
                
                case UPLOAD_ERR_NO_FILE:
                    throw new \App\Exception\DisplayOnly('File Upload Error: No file was uploaded.');
                    break;
                
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \App\Exception\DisplayOnly('File Upload Error: Missing a temporary folder.');
                    break;
                
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \App\Exception\DisplayOnly('File Upload Error: Failed to write file to disk.');
                    break;
                
                case UPLOAD_ERR_EXTENSION:
                    throw new \App\Exception\DisplayOnly('File Upload Error: Upload stopped by extension.');
                    break;
                    
                default:
                    throw new \App\Exception\DisplayOnly('File Upload Error: No file was specified.');
                    break;
            }
            exit;
        }
        
        if (empty($file_name))
        {
            $file_name = basename($uploaded_file['name']);
        }
        
        // Replace .??? with the correct file extension if listed.
        $file_name = str_replace('.???', '.'.self::getFileExtension($uploaded_file['name']), $file_name);
        
        $upload_path = self::getFilePath($file_name);
        
        if (move_uploaded_file($uploaded_file['tmp_name'], $upload_path))
        {
            return $file_name;
        }
        else
        {
            throw new \App\Exception\DisplayOnly('File Upload Error: Could not upload the file requested.');
            exit;
        }
    }
    
    public static function createFileFromData($file_name, $file_data)
    {
        file_put_contents(self::getFilePath($file_name), $file_data);
        return $file_name;
    }
        
    public static function getFilePointer($file_name)
    {
        return fopen(self::getFilePath($file_name), 'r');
    }

    public static function getFileContents($file_name)
    {
        return file_get_contents(self::getFilePath($file_name));
    }
    
    public static function getCSV($file_name)
    {
        @ini_set('auto_detect_line_endings', 1);
        
        $csv_data = array();
        $handle = fopen(self::getFilePath($file_name), "r");
        while (($data = fgetcsv($handle)) !== FALSE)
        {
            $csv_data[] = $data;
        }
        
        fclose($handle);
        return $csv_data;
    }
    
    // Returns a "clean" array with the first row's text as the column names.
    public static function getCleanCSV($file_name)
    {
        $csv_data = self::getCSV($file_name);
        $clean_data = array();
        
        if ($csv_data)
        {
            $headers = array();
            $row_num = 0;
            $col_num = 0;
            
            $header_row = array_shift($csv_data);
            foreach($header_row as $csv_col)
            {
                $field_name = strtolower(preg_replace("/[^a-zA-Z0-9_]/", "", $csv_col));
                if (!empty($field_name))
                    $headers[$col_num] = $field_name;
                $col_num++;
            }
            
            foreach($csv_data as $csv_row)
            {
                $col_num = 0;
                $clean_row = array();
                foreach($csv_row as $csv_col)
                {
                    $col_name = (isset($headers[$col_num])) ? $headers[$col_num] : $col_num;
                    $clean_row[$col_name] = $csv_col;
                    $col_num++;
                }
                
                $clean_data[] = $clean_row;
                $row_num++;
            }
        }
        return $clean_data;
    }
    
    public static function renameFile($old_file, $new_file)
    {
        $old_file_path = self::getFilePath($old_file);
        $new_file_path = self::getFilePath($new_file);
        
        rename($old_file_path, $new_file_path);
        
        return $new_file;
    }
    
    public static function deleteFile($file_name)
    {
        unlink(self::getFilePath($file_name));
    }
}