<?php

namespace Baseapp\Library;

require_once __DIR__ . '/Email/class.phpmailer.php';

/**
 * Email Library
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Email extends \PHPMailer
{

    /**
     * Email constructor
     *
     * @package     base-app
     * @version     2.0
     *
     * @return object PHPMailer
     */
    public function __construct()
    {
        $email = new \PHPMailer();

        // Load email config from config.ini
        if ($config = \Phalcon\DI::getDefault()->getShared('config')->email) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }

        return $email;
    }

    /**
     * Get email template and load view with params
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $view view name to load
     * @param array $params params to send to the view
     *
     * @return string
     */
    public function getTemplate($name, $params = array())
    {
        $view = \Phalcon\DI::getDefault()->getShared('view');
        $view->getRender('email', $name, $params, function($callback) {
            $callback->setRenderLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);
        });
        return $view->getContent();
    }

    /**
     * Prepare email - set title, recipment and body
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $subject email subject
     * @param string $to email recipment
     * @param string $view view name to load
     * @param array $params params to send to the view
     *
     * @return string
     */
    public function prepare($subject, $to, $view, $params = array())
    {
        $this->Subject = $subject;
        $this->AddAddress($to);

        // Load email content from template and view
        $body = $this->getTemplate($view, $params);
        $this->MsgHTML($body);

        return $body;
    }

}
