<?php
namespace DF\View\Helper;
class PubNub extends \Zend_View_Helper_Abstract
{
    public function pubNub()
    {
        static $is_attached;
        
        if (!$is_attached)
        {
            $config = \Zend_Registry::get('config');
            $settings = $config->services->pubnub->toArray();
            
            $pubnub_settings = array(
                'publish_key'       => $settings['pub_key'],
                'subscribe_key'     => $settings['sub_key'],
                'origin'            => 'pubsub.pubnub.com',
                'ssl'               => (DF_IS_SECURE) ? true : false,
            );
            
            $this->view->headScript()->appendFile('http://cdn.pubnub.com/pubnub-3.1.min.js');
            $this->view->headScript()->appendScript('
                (function(){
                    var pubnub = PUBNUB.init('.json_encode($pubnub_settings).');
                    $.pubnub = pubnub;
                })();
            ');
            
            $is_attached = TRUE;
        }
    }
}