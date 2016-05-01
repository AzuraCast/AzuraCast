<?php
namespace App\Auth;

use Entity\User;
use Entity\UserVariable;

use App\Common\MagicProperties;

/**
 * A placeholder class for non-logged-in users.
 *
 * @package App\Auth
 */
class AnonymousUser extends MagicProperties
{
    public function getAccessLevel()
    {
        return User::LEGACY_ACL_REGULAR;
    }

    public function getAvatar()
    {
        $di = \Phalcon\Di::getDefault();

        $default_avatar = $di['url']->getStatic('img/avatar.gif');
        return $default_avatar;
    }

    public function setAvatar($new_path)
    {}

    public function getNotifications()
    {
        $notifications = array();
        $notify_types = User::getNotificationTypes();

        foreach($notify_types as $notify_key => $notify_info)
        {
            $notify_info['count'] = 0;
            $notify_info['show'] = FALSE;
            $notify_info['text'] = '';

            $notifications[$notify_info['short']] = $notify_info;
        }
    }

    public function getVariables()
    {
        static $vars;

        if (!$vars)
        {
            $var_definitions = UserVariable::getDefinitions();

            $vars = array();
            foreach($var_definitions as $var_key => $var_info)
                $vars[$var_key] = $var_info['default'];
        }

        return $vars;
    }

    public function getVariable($key)
    {
        $vars = $this->getVariables();

        if (isset($vars[$key]))
            return $vars[$key];
        else
            return null;
    }

    public function setVariable($key)
    {}

    public function setVariables($keys)
    {}

    public function deleteVariable($key)
    {}

}