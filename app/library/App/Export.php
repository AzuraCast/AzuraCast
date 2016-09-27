<?php
/**
 * Class with static methods for exporting data into various formats.
 */

namespace App;
class Export
{
    /**
     * CSV
     **/
    
    public static function csv($table_data, $headers_first_row = TRUE, $file_name = "ExportedData")
    {
        self::exportToCSV($table_data, $headers_first_row, $file_name);
    }
    
    public static function exportToCSV($table_data, $headers_first_row = TRUE, $file_name = "ExportedData")
    {
        // Header data associated with CSV files.
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", FALSE);
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".$file_name.".csv");
        
        echo self::convertToCSV($table_data, $headers_first_row);
        exit;
    }
    
    public static function convertToCSV($table_data, $headers_first_row = FALSE)
    {
        $final_display = array();
        $row_count = 0;
        foreach($table_data as $table_row)
        {
            $row_count++;
            $col_count = 0;
            $display_row1 = array();
            $display_row2 = array();
            foreach($table_row as $table_col => $table_val)
            {
                $col_count++;
                if (!$headers_first_row && $row_count == 1)
                {
                    $display_row1[] = '"'.self::filterTextToCSV($table_col).'"';
                }
                $display_row2[] = '"'.self::filterTextToCSV($table_val).'"';
            }
            
            if ($display_row1)
            {
                $final_display[] = implode(',', $display_row1);
            }
            if ($display_row2)
            {
                $final_display[] = implode(',', $display_row2);
            }
        }
        return implode("\n", $final_display);
    }
    
    public static function filterTextToCSV($text)
    {
        return str_replace('"', '""', $text);
    }
        
    /**
     * JSON
     **/
    
    public static function json($table_data)
    {
        return json_encode($table_data);
    }
    public static function exportToJSON($table_data)
    {
        return json_encode($table_data);
    }
    
    /**
     * XML to Array
     */ 
    public static function XmlToArray($xml)
    {
        $values = $index = $array = array();
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $xml, $values, $index);
        xml_parser_free($parser);
        $i = 0;
        $name = $values[$i]['tag'];
        $array[$name] = isset($values[$i]['attributes']) ? $values[$i]['attributes'] : '';
        $array[$name] = self::_struct_to_array($values, $i);
        return $array;
    }

    protected static function _struct_to_array($values, &$i)
    {
        $child = array();
        if (isset($values[$i]['value'])) array_push($child, $values[$i]['value']);
       
        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                    array_push($child, $values[$i]['value']);
                    break;
               
                case 'complete':
                    $name = $values[$i]['tag'];
                    if(!empty($name)){
                        $child[$name]= ($values[$i]['value'])?($values[$i]['value']):'';
                        if(isset($values[$i]['attributes'])) {                   
                            $child[$name] = $values[$i]['attributes'];
                        }
                    }   
                    break;
               
                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    $child[$name][$size] = self::_struct_to_array($values, $i);
                    break;
               
                case 'close':
                    return $child;
                    break;
            }
        }
        return $child;
    }

    public static function ArrayToXml($array)
    {
        $xml_info = new \SimpleXMLElement('<?xml version="1.0"?><return></return>');
        self::_arr_to_xml($array, $xml_info);

        return $xml_info->asXML();
    }

    protected static function _arr_to_xml($array, &$xml)
    {
        foreach((array)$array as $key => $value)
        {
            if(is_array($value))
            {
                $key = is_numeric($key) ? "item$key" : $key;
                $subnode = $xml->addChild("$key");

                self::_arr_to_xml($value, $subnode);
            }
            else
            {
                $key = is_numeric($key) ? "item$key" : $key;
                $xml->addChild("$key", htmlspecialchars($value));
            }
        }
    }
    
    /**
     * iCal
     */
    
    public static function iCal($options)
    {
        $defaults = array(
            'priority'  => 0,
            'uid'       => date('Ymd').'T'.date('His').'-'.rand().'-dsa.tamu.edu',
            'organizer' => 'noreply@dsa.tamu.edu',
            'reminder'  => '-PT15M',
            'name'      => 'Event',
            'desc'      => 'Event Invitation',
            'location'  => 'TBD',
        );
        $options = array_merge($defaults, $options);
        
        $ical_lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Microsoft Corporation//Outlook 12.0 MIMEDIR//EN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'ORGANIZER:MAILTO:'.$options['organizer'],
            'UID:'.$options['uid'], // required by Outlok
            'DTSTAMP:'.date('Ymd').'T'.date('His').'Z', // required by Outlook
            'CREATED:'.date('Ymd').'T'.date('His').'Z', // required by Outlook
            'LAST-MODIFIED:'.date('Ymd').'T'.date('His').'Z', // required by Outlook
            'DTSTART:'.date('Ymd', $options['start_timestamp']).'T'.date('His', $options['start_timestamp']).'Z',
            'DTEND:'.date('Ymd', $options['end_timestamp']).'T'.date('His', $options['end_timestamp']).'Z',
            'SUMMARY:'.$options['name'],
            'DESCRIPTION:'.$options['desc'],
            'LOCATION:'.$options['location'],
            'PRIORITY:'.$options['priority'],
            'CLASS:PUBLIC',
            'TRANSP:OPAQUE',
            'TRIGGER:'.$options['reminder'],
            'END:VEVENT',
            'END:VCALENDAR',
        );
        
        return trim(implode("\r\n", $ical_lines));
    }
}