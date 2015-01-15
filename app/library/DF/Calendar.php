<?php
/**
 * Calendar generation helper
 */

namespace DF;
class Calendar
{
    protected $_datecode;
    protected $_month;
    protected $_year;
    
    protected $_start_timestamp;
    protected $_mid_timestamp;
    protected $_end_timestamp;
    protected $_records;
    
    public function __construct($datecode = NULL)
    {
        $datecode = ($datecode) ? (int)$datecode : date('Ym');
        
        $this->setDateCode($datecode);
        $this->_records = array();
    }
    
    public function setDateCode($datecode)
    {
        $this->_datecode = $datecode;
        $this->_month = (int)substr($datecode, 4, 2);
        $this->_year = (int)substr($datecode, 0, 4);
        
        $mid_timestamp = mktime(0, 0, 0, $this->_month, 15, $this->_year);
        
        if (!$this->isValidDate($mid_timestamp))
        {
            throw new \DF\Exception\DisplayOnly('Invalid date/time specified.');
        }
        
        $this->_start_timestamp = mktime(0, 0, 0, $this->_month, 1, $this->_year);
        $this->_mid_timestamp = $mid_timestamp;
        $this->_end_timestamp = mktime(0, 0, 0, $this->_month+1, 1, $this->_year);
    }
    
    public function getDateCode()
    {
        return $this->_datecode;
    }
    
    public function getTimestamps()
    {
        return array(
            'start'     => $this->_start_timestamp,
            'mid'       => $this->_mid_timestamp,
            'end'       => $this->_end_timestamp,
        );
    }
    
    public function setRecords($records)
    {
        $this->_records = $records;
    }
    
    public function fetch($records = NULL)
    {
        if ($records)
            $this->setRecords($records);
        
        $return_vars = array();
        $calendar_days = array();
        
        // Current page.
        $current_page_timestamp = $this->_mid_timestamp;
        $return_vars['current_page_datecode'] = date('Ym', $current_page_timestamp);
        $return_vars['current_page_text'] = date('F Y', $current_page_timestamp);
        
        // Surrounding pages.
        $prev_page_timestamp = strtotime("-1 month", $this->_mid_timestamp);
        if ($this->isValidDate($prev_page_timestamp))
        {
            $return_vars['prev_page_datecode'] = date('Ym', $prev_page_timestamp);
            $return_vars['prev_page_text'] = date('F Y', $prev_page_timestamp);
        }
        
        $next_page_timestamp = strtotime("+1 month", $this->_mid_timestamp);
        if ($this->isValidDate($next_page_timestamp))
        {
            $return_vars['next_page_datecode'] = date('Ym', $next_page_timestamp);
            $return_vars['next_page_text'] = date('F Y', $next_page_timestamp);
        }
        
        // Retrieves the day (Sunday = 0, Monday = 1, etc.) of the first day of the month.
        $first_calendar_day = date('w', $this->_start_timestamp);
        $days_in_previous_month = date('t', $prev_page_timestamp);
        
        // Creates the cells containing the previous month's days, starting the first row.
        for($i = 0; $i < $first_calendar_day+1; $i++)
        {
            $calendar_days[$i+1] = array(
                'day'       => $days_in_previous_month - ($first_calendar_day - $i) + 1,
                'disabled'  => true,
                'records'   => array(),
            );
        }
        
        // Creates the cells containing the current month's days.
        $k = $first_calendar_day+1;
        $starting_index = $k;
        
        $days_in_current_month = date('t', $this->_mid_timestamp);
        
        for($i = 0; $i < $days_in_current_month; $i++)
        {
            $calendar_days[$i+$k] = array(
                'day'       => $i+1, 
                'records'   => array()
            );
        }
        
        // Creates the cells containing the next month's days, finishing the last row.
        $k = $days_in_current_month + $k - 1;
        $last_calendar_day = date('w', $this->_end_timestamp);
        
        $j = 1;
        for($i = $last_calendar_day+1; $i <= 7; $i++)
        {
            $calendar_days[$k+$j] = array(
                'day'       => $j,
                'disabled'  => true,
                'class'     => 'disabled',
                'records'   => array()
            );
            $j++;
        }
        
        $today_info = array('year' => date('Y'), 'month' => date('m'), 'day' => date('d'));
        $today = new \Zend_Date($today_info);
        $today_timestamp = $today->getTimestamp();
        
        // Create timestamp values for each day.
        $day_timestamps = array();
        for($i = 0; $i < $days_in_current_month; $i++)
        {
            $date_info = array(
                'year'  => $this->_year,
                'month' => $this->_month,
                'day'   => $i+1,
            );
            $date = new \Zend_Date($date_info);
            $date_timestamp = $date->getTimestamp();
            
            $k = $starting_index+$i;
            $day_timestamps[$k] = array(
                'start'     => $date_timestamp,
                'end'       => $date_timestamp + 86399,
            );
            
            if ($date_timestamp == $today_timestamp)
                $calendar_days[$k]['class'] = 'blue';
        }
        
        // Populate records into days.
        if ($this->_records)
        {   
            foreach($this->_records as $record)
            {
                // Determine the "start" and "end" timestamps for the item.
                if (isset($record['timestamp']))
                {
                    $start_timestamp = (int)$record['timestamp'];
                    $end_timestamp = (int)$record['timestamp'];
                }
                else if (isset($record['start_timestamp']))
                {
                    $start_timestamp = (int)$record['start_timestamp'];
                    $end_timestamp = (int)$record['end_timestamp'];
                }
                else
                {
                    break;
                }
                
                if ($start_timestamp && $end_timestamp)
                {
                    foreach($day_timestamps as $i => $timestamps)
                    {
                        $day_start = $timestamps['start'];
                        $day_end = $timestamps['end'];

                        $day_record = $record;
                        $day_record['starts_today'] = ($start_timestamp > $day_start && $start_timestamp < $day_end);
                        $day_record['ends_today'] = ($end_timestamp > $day_start && $end_timestamp < $day_end);
                        $day_record['only_today'] = ($day_record['starts_today'] && $day_record['ends_today']);

                        if ($day_record['is_all_day'])
                            $day_record['time'] = 'All Day';
                        elseif ($day_record['only_today'])
                            $day_record['time'] = date('g:ia', $start_timestamp).' to '.date('g:ia', $end_timestamp);
                        elseif ($day_record['starts_today'])
                            $day_record['time'] = 'Starts '.date('g:ia', $start_timestamp);
                        elseif ($day_record['ends_today'])
                            $day_record['time'] = 'Ends '.date('g:ia', $end_timestamp);
                        else
                            $day_record['time'] = 'All Day';
                        
                        if ($start_timestamp < $day_end && $end_timestamp > $day_start)
                        {
                            $calendar_days[$i]['records'][] = $day_record;
                        }
                    }
                }
            }
        }
        
        // Reassign calendar days into rows.
        $new_calendar_days = array();
        
        foreach($calendar_days as $calendar_day_num => $calendar_day_info)
        {
            $current_row = (int)floor(($calendar_day_num-1) / 7);
            $new_calendar_days[$current_row][] = $calendar_day_info;
        }
        
        $return_vars['days'] = $new_calendar_days;
        return $return_vars;
    }
    
    protected function isValidDate($timestamp)
    {
        $threshold = 86400 * 365 * 5; // 5 Years
        return ($timestamp >= (time() - $threshold) && $timestamp <= (time() + $threshold));
    }
}