<?php
/**
 * Messenger Class (E-mail Delivery)
 */

namespace App;

use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;

class Messenger
{
    /**
     * New messenger method:
     * Uses an expandable array of options and supports direct template rendering and subject prepending.
     *
     * @param array $message_options An array of message options.
     * @return bool|void
     */
    public static function send($message_options)
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');
        
        $default_options = array(
            'reply_to'          => NULL,
            'delivery_date'     => NULL,
            'options'           => NULL,
        );
        $options = array_merge($default_options, $message_options);
        
        // Render the template as the message if a template is specified.
        if (isset($options['template']))
        {
            $previous_sp_setting = $di['url']->getSchemePrefixSetting();
            $di['url']->forceSchemePrefix(TRUE);

            $view = \App\Phalcon\View::getView(array(
                'views_dir' => 'messages',
                'layouts_dir' => '../templates',
                'layout'    => 'message',
            ));
            
            $view->subject = $options['subject'];
            $view->setVars((array)$options['vars']);

            $options['message'] = $view->getRender('', $options['template']);

            $di['url']->forceSchemePrefix($previous_sp_setting);
        }
        else if (isset($options['body']) && !isset($options['message']))
        {
            $options['message'] = $options['body'];
            unset($options['body']);
        }
        
        // Append the system name as a prefix to the message.
        if (!isset($options['no_prefix']) || $options['no_prefix'] == FALSE)
        {
            $app_name = $config->application->name;
            $options['subject'] = $app_name.': '.$options['subject'];
        }
        
        return self::sendMail($options);
    }

    /**
     * Handle message delivery.
     *
     * @return bool
     */
    public static function sendMail()
    {
        // Allow support for legacy argument style or new style.
        $args = func_get_args();
        if (func_num_args() == 1)
        {
            $options = $args[0];
        }
        else
        {
            $options = array_merge(array(
                'to'        => $args[0],
                'subject'   => $args[1],
                'message'   => $args[2],
                'reply_to'  => $args[3],
                'delivery_date' => $args[4],
            ), $args[5]);
        }

        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $mail_config = $config->application->mail->toArray();

        // Do not deliver mail on development environments.
        if (DF_APPLICATION_ENV == "development" && !defined('DF_FORCE_EMAIL'))
        {
            $email_to = $mail_config['from_addr'];
            if (!empty($email_to))
                $options['to'] = $email_to;
            else
                return false;
        }

        if (isset($mail_config['use_smtp']) && $mail_config['use_smtp'])
        {
            $smtp_config = $config->apis->smtp->toArray();
            $smtp_config['host'] = $smtp_config['server'];
            unset($smtp_config['server']);

            $transport = new SmtpMailer($smtp_config);
        }
        else
        {
            $transport = new SendmailMailer();
        }
        
        if (!is_array($options['to']))
            $options['to'] = array($options['to']);
        else
            $options['to'] = array_unique($options['to']);
        
        foreach((array)$options['to'] as $mail_to_addr)
        {
            if (empty($mail_to_addr))
                continue;

            $mail_to_addr = str_replace('mailto:', '', $mail_to_addr);

            $mail = new Message;
            $mail->setSubject($options['subject']);

            $from_addr = (isset($options['from'])) ? $options['from'] : $mail_config['from_addr'];
            $from_name = (isset($options['from_name'])) ? $options['from_name'] : $mail_config['from_name'];
            $mail->setFrom($from_addr, $from_name);

            if (isset($mail_config['bounce_addr']))
                $mail->setReturnPath($mail_config['bounce_addr']);

            /*
            // Include a specific "Direct replies to" header if specified.
            if ($options['reply_to'] && $validator->isValid($options['reply_to']))
                $mail->setReplyTo($options['reply_to']);
            else if (isset($mail_config['reply_to']) && $mail_config['reply_to'])
                $mail->setReplyTo($mail_config['reply_to']);
            */

            // Change the type of the e-mail's body if specified in the options.
            if (isset($options['text_only']) && $options['text_only'])
                $mail->setBody(strip_tags($options['message']));
            else
                $mail->setHtmlBody($options['message'], false);

            // Add attachment if specified in options.
            if (isset($options['attachments']))
            {
                foreach((array)$options['attachments'] as $attachment)
                {
                    $mail->addAttachment($attachment);
                }
            }

            /*
            // Modify the mail type if specified.
            if (isset($options['type']))
                $mail->setType($options['type']);
            */

            // Catch invalid e-mails.
            try
            {
                $mail->addTo($mail_to_addr);
            }
            catch(\Nette\Utils\AssertionException $e)
            {
                continue;
            }

            $transport->send($mail);
        }

        return true;
    }
}