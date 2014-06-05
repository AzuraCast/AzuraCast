<?php
namespace DF\View\Helper;
class FormUnixDate extends \Zend_View_Helper_FormElement
{
    public function formUnixDate($name, $orig_value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $orig_value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
        
        if (empty($attribs))
        {
            $element = new \DF\Form\Element\UnixDate($name);
            $element->setValue($orig_value);
            $attribs = (array)$element->getAttribs();
        }
        
        $config = \Zend_Registry::get('config');
        $view = $this->view;
        $markup = array();

        if ($attribs['use_datepicker'] != true)
        {
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

            return implode(' ', $markup);
        }
        else
        {
            // bootstrap datepicker: http://www.eyecon.ro/bootstrap-datepicker/
            $markup = '<span class="input-append">';
            $markup .= $view->formText($name, date('m/d/Y', $attribs['field_timestamp']), array(
                'class' => 'datepicker input-small',
                'placeholder' => 'mm/dd/yyyy',
            ));
            $markup .= '<button class="btn" type="button" data-date-format="mm/dd/yyyy" data-date="'.date('m/d/Y', $attribs['field_timestamp']).'"><i class="icon-th"></i></button></span>';
            $markup .= '<p class="help-block">mm/dd/yyyy</p>';

            return $markup;
        }
    }
}