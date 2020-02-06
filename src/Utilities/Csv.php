<?php
namespace App\Utilities;

class Csv
{
    /**
     * Generate a CSV-compatible file body given an array.
     *
     * @param array $table_data
     * @param bool $headers_first_row
     *
     * @return string
     */
    public static function arrayToCsv($table_data, $headers_first_row = true)
    {
        $final_display = [];
        $row_count = 0;
        foreach ($table_data as $table_row) {
            $row_count++;
            $col_count = 0;

            $header_row = [];
            $body_row = [];

            foreach ($table_row as $table_col => $table_val) {
                $col_count++;
                if (!$headers_first_row && $row_count == 1) {
                    $header_row[] = '"' . str_replace('"', '""', $table_col) . '"';
                }

                $body_row[] = '"' . str_replace('"', '""', $table_val) . '"';
            }

            if ($header_row) {
                $final_display[] = implode(',', $header_row);
            }

            if ($body_row) {
                $final_display[] = implode(',', $body_row);
            }
        }

        return implode("\n", $final_display);
    }
}