<?php
namespace DF\View\Helper;
class Analytics extends \Zend_View_Helper_Abstract
{
    public function analytics($account_number)
    {
        $analytics_script = "
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', '".$account_number."']);
            _gaq.push(['_trackPageview']);
            
            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        ";
            
        return '<script type="text/javascript">'.preg_replace("/\t+/", "", $analytics_script).'</script>';
    }
}