<?php

declare(strict_types=1);

namespace App\Service;

use App\Utilities\File;
use League\Csv\AbstractCsv;
use League\Csv\Writer;

class CsvWriterTempFile
{
    protected string $tempPath;

    protected Writer $writer;

    public function __construct()
    {
        $this->tempPath = File::generateTempPath('temp_file.csv');

        // Append UTF-8 BOM to temp file.
        file_put_contents($this->tempPath, AbstractCsv::BOM_UTF8);

        $this->writer = Writer::createFromPath($this->tempPath, 'a+');
    }

    public function getWriter(): Writer
    {
        return $this->writer;
    }

    public function getTempPath(): string
    {
        return $this->tempPath;
    }

    public function __destruct()
    {
        @unlink($this->tempPath);
    }
}
