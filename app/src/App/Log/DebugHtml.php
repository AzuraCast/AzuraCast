<?php
namespace App\Log;

use Monolog\Logger;

/**
 * Formats incoming records into a preformatted echo style.
 */
class DebugHtml extends \Monolog\Formatter\HtmlFormatter
{
    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $title = $record['level_name'].': '.(string)$record['message'];
        $title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');

        $output = '<div style="font-family: Consolas, Courier New, Courier, monospace; 
                        font-size: 12px; 
                        background: #EEE;
                        color: #111;
                        border-left: 4px solid '.$this->logLevels[$record['level']].'; 
                        border-bottom: 1px solid #DDD;
                        margin: 0 0 5px 0;">';

        $output .= '<h2 style="
                        margin: 0;
                        padding: 3px;
                        font-family: Consolas, Courier New, Courier, monospace; 
                        font-size: 13px;
                        font-weight: bold;
                        background: '.$this->logLevels[$record['level']].';
                    ">'.$title.'</h2>';

        if ($record['context'] || $record['extra']) {

            $output .= '<div style="padding: 3px;">';

            if ($record['context']) {
                foreach ($record['context'] as $key => $value) {
                    $output .= '<h5>' . $key . '</h5><div style="white-space: pre;">' . $this->convertToString($value) . '</div>';
                }
            }

            if ($record['extra']) {
                foreach ($record['extra'] as $key => $value) {
                    $output .= '<h5>' . $key . '</h5><div style="white-space: pre;">' . $this->convertToString($value) . '</div>';
                }
            }

            $output .= '</div>';
        }

        return $output.'</div>';
    }
}
