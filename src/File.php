<?php
namespace App;

use Psr\Http\Message\UploadedFileInterface;
use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_PARTIAL;

/**
 * Static class that facilitates the uploading, reading and deletion of files in a controlled directory.
 */
class File
{
    /**
     * @var string The file's name (the portion after the base directory).
     */
    protected $name;

    /**
     * @var string The base directory in which the file is uploaded.
     */
    protected $base_dir;

    public function __construct($file_name, $base_dir)
    {
        $this->name = $file_name;
        $this->base_dir = $base_dir;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function sanitizePathPrefix(string $path): string
    {
        $pattern = '/.*:\/\//i';

        $path = preg_replace($pattern, '', $path);

        if (preg_match($pattern, $path)) {
            return self::sanitizePathPrefix($path);
        }

        return $path;
    }

    /**
     * Sanitize a user-specified filename for storage.
     * Credit to: http://stackoverflow.com/a/19018736
     *
     * @param string $str
     *
     * @return string
     */
    public static function sanitizeFileName($str): string
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '_', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $file_name
     */
    public function setName($file_name)
    {
        $this->name = $file_name;
    }

    public function sanitizeName()
    {
        $this->name = self::sanitizeFileName($this->name);
    }

    /**
     * Add a suffix to a file *before* its extension.
     *
     * @param string $suffix
     *
     * @return $this
     */
    public function addSuffix($suffix)
    {
        $file_parts = pathinfo($this->name);
        $new_file_name = $file_parts['filename'] . $suffix . '.' . $file_parts['extension'];

        if ($file_parts['dirname'] != '.') {
            $this->name = $file_parts['dirname'] . DIRECTORY_SEPARATOR . $new_file_name;
        } else {
            $this->name = $new_file_name;
        }

        return $this;
    }

    /**
     * Get the file's extension.
     * @return string
     */
    public function getExtension()
    {
        // Significantly more performant than using pathinfo function.
        return substr($this->name, strrpos($this->name, '.') + 1);
    }

    /**
     * Attempt to move an uploaded file to the file name specified by the object.
     *
     * @param array|object $uploaded_file
     *
     * @return bool
     * @throws Exception
     */
    public function upload($uploaded_file)
    {
        if ($uploaded_file instanceof UploadedFileInterface) {
            return $uploaded_file->moveTo($this->getPath());
        }

        if (!$this->isValid($uploaded_file)) {
            switch ($uploaded_file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new Exception('File Upload Error: The file you are attempting to upload is larger than allowed (upload_max_filesize).');
                    break;

                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File Upload Error: The file you are attempting to upload is larger than allowed (MAX_FILE_SIZE).');
                    break;

                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('File Upload Error: The file you are attempting to upload was only partially uploaded.');
                    break;

                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('File Upload Error: No file was uploaded.');
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('File Upload Error: Missing a temporary folder.');
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('File Upload Error: Failed to write file to disk.');
                    break;

                case UPLOAD_ERR_EXTENSION:
                    throw new Exception('File Upload Error: Upload stopped by extension.');
                    break;

                default:
                    throw new Exception('File Upload Error: No file was specified.');
                    break;
            }
        }

        if (move_uploaded_file($uploaded_file['tmp_name'], $this->getPath())) {
            return true;
        } else {
            throw new Exception('File Upload Error: Could not upload the file requested.');
        }
    }

    /**
     * Return the full path of the file.
     * @return string
     */
    public function getPath()
    {
        $file_name = trim($this->name);
        $file_name = ltrim($file_name, '/');
        $file_name = str_replace('/', DIRECTORY_SEPARATOR, $file_name);

        return $this->base_dir . DIRECTORY_SEPARATOR . $file_name;
    }

    /**
     * Check if an uploaded file (from the $_FILES array) is valid.
     *
     * @param array $uploaded_file
     *
     * @return bool
     */
    public function isValid($uploaded_file)
    {
        return (!empty($uploaded_file) && $uploaded_file['error'] == UPLOAD_ERR_OK);
    }

    /**
     * Create the specified file containing a string passed to the function.
     * Passes flags on to file_put_contents.
     *
     * @param string $file_data
     * @param mixed|null $flags
     *
     * @return $this
     */
    public function putContents($file_data, $flags = null)
    {
        file_put_contents($this->getPath(), $file_data, $flags);

        return $this;
    }

    /**
     * Return the file's contents as a string.
     * @return string
     */
    public function getContents()
    {
        return file_get_contents($this->getPath());
    }

    /**
     * Returns a "clean" array with the first row's text as the column names.
     * @return array
     */
    public function getCleanCsv()
    {
        $csv_data = $this->getCsv();
        $clean_data = [];

        if ($csv_data) {
            $headers = [];
            $row_num = 0;
            $col_num = 0;

            $header_row = array_shift($csv_data);
            foreach ($header_row as $csv_col) {
                $field_name = strtolower(preg_replace("/[^a-zA-Z0-9_]/", "", $csv_col));
                if (!empty($field_name)) {
                    $headers[$col_num] = $field_name;
                }
                $col_num++;
            }

            foreach ($csv_data as $csv_row) {
                $col_num = 0;
                $clean_row = [];
                foreach ($csv_row as $csv_col) {
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

    /**
     * Get raw CSV data from a file.
     * @return array
     */
    public function getCsv()
    {
        @ini_set('auto_detect_line_endings', 1);

        $csv_data = [];
        $handle = $this->getPointer();
        while (($data = fgetcsv($handle)) !== false) {
            $csv_data[] = $data;
        }

        fclose($handle);

        return $csv_data;
    }

    /**
     * Get a fopen resource pointer to the file.
     *
     * @param string $mode
     *
     * @return resource
     */
    public function getPointer($mode = 'r')
    {
        return fopen($this->getPath(), $mode);
    }

    /**
     * Rename the file to the new name specified, preserving the base directory.
     *
     * @param string $new_name
     *
     * @return $this
     */
    public function rename($new_name)
    {
        $old_path = $this->getPath();

        $this->setName($new_name);
        $new_path = $this->getPath();

        if (file_exists($old_path)) {
            rename($old_path, $new_path);
        }

        return $this;
    }

    /**
     * Delete the file.
     */
    public function delete()
    {
        unlink($this->getPath());
    }
}
