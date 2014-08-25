<?
namespace DF\Application\Resource;

class Session extends \Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);

        if (isset($options['savehandler']))
            unset($options['savehandler']);

        if (isset($options['use_database']))
        {
            unset($options['use_database']);

            $em = \Zend_Registry::get('em');

            $sh = new \DF\Doctrine\Session\SaveHandler($em);
            \Zend_Session::setSaveHandler($sh);
        }

        \Zend_Session::setOptions($options);
    }
}