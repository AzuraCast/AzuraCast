<?php

declare(strict_types=1);

namespace App\Utilities;

use League\Csv\EscapeFormula;
use League\Csv\Reader;
use League\Csv\Writer;

final class Csv
{
    /**
     * @param resource $stream
     */
    public static function exportWriter($stream): Writer
    {
        return Writer::from($stream)
            ->addFormatter(self::escapeFormula()->escapeRecord(...));
    }

    public static function importReader(string $path): Reader
    {
        return Reader::from($path)
            ->addFormatter(self::escapeFormula()->unescapeRecord(...));
    }

    private static function escapeFormula(): EscapeFormula
    {
        return new EscapeFormula();
    }
}
