<?php
namespace DF\View\Helper;
class FormUnixDateTime extends \Zend_View_Helper_FormElement
{
    public function formUnixDateTime($name, $orig_value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $orig_value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
        
        if (empty($attribs))
        {
            $element = new \DF\Form\Element\UnixDateTime($name);
            $element->setValue($orig_value);
            $attribs = (array)$element->getAttribs();
        }
        
        $config = \Zend_Registry::get('config');
        $view = $this->view;
        $markup = array();

        $show_blank = (isset($attribs['blank']) && $attribs['blank']);

        // Month
        $month_opts = $config->general->months->toArray();

        if ($show_blank)
            $month_opts = array('' => 'Month') + $month_opts;
        else if ($attribs['field_timestamp'] == 0)
            $attribs['field_month'] = date('m');

        $markup[] = $view->formSelect($name.'[month]', $attribs['field_month'], NULL, $month_opts);

        // Day
        $day_opts = array();
        for($i = 1; $i <= 31; $i++)
            $day_opts[$i] = $i;
        
        if ($show_blank)
            $day_opts = array('' => 'Day') + $day_opts;
        else if ($attribs['field_timestamp'] == 0)
            $attribs['field_day'] = date('d');

        $markup[] = $view->formSelect($name.'[day]', $attribs['field_day'], NULL, $day_opts);

        // Year
        $year_opts = array();
        
        $start_year = ($attribs['start_year']) ? $attribs['start_year'] : date('Y')-5;
        $end_year = ($attribs['end_year']) ? $attribs['end_year'] : date('Y')+5;

        for($i = $start_year; $i <= $end_year; $i++)
            $year_opts[$i] = $i;
        
        if ($show_blank)
            $year_opts = array('' => 'Year') + $year_opts;
        else if ($attribs['field_timestamp'] == 0)
            $attribs['field_year'] = date('Y');

        $markup[] = $view->formSelect($name.'[year]', $attribs['field_year'], NULL, $year_opts);

        // Hour
        $hour_opts = array();
        for($i = 1; $i <= 12; $i++)
            $hour_opts[$i] = $i;
        
        if ($show_blank)
            $hour_opts = array('' => 'Hour') + $hour_opts;
        else if ($attribs['field_timestamp'] == 0)
            $attribs['field_hour'] = date('g');

        $markup[] = $view->formSelect($name.'[hour]', $attribs['field_hour'], NULL, $hour_opts);

        // Minute
        $minute_opts = array();
        for($i = 0; $i <= 60; $i++)
            $minute_opts[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);

        if ($show_blank)
            $minute_opts = array('' => 'Minute') + $minute_opts;
        else if ($attribs['field_timestamp'] == 0)
            $attribs['field_minute'] = date('i');
        
        $markup[] = $view->formSelect($name.'[minute]', $attribs['field_minute'], NULL, $minute_opts);
        
        // Meridian
        $meridian_opts = array('am' => 'AM', 'pm' => 'PM');

        if (!$show_blank && $attribs['field_timestamp'] == 0)
            $attribs['field_meridian'] = date('a');
        
        $markup[] = $view->formSelect($name.'[meridian]', $attribs['field_meridian'], NULL, $meridian_opts);

        return implode(' ', $markup);
    }
}