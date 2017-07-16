<?php
/**
 * Messenger Class (E-mail Delivery)
 */

namespace App;

use Interop\Container\ContainerInterface;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;

class Messenger
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
    }

    /**
     * New messenger method:
     * Uses an expandable array of options and supports direct template rendering and subject prepending.
     *
     * @param array $message_options An array of message options.
     * @return bool|void
     */
    public function send($message_options)
    {
        $config = $this->di['config'];

        $default_options = [
            'reply_to' => null,
            'delivery_date' => null,
            'options' => null,
        ];
        $options = array_merge($default_options, $message_options);

        // Render the template as the message if a template is specified.
        if (isset($options['template'])) {
            $vars = (array)$options['vars'];
            $vars['subject'] = $options['subject'];

            $view = $this->di['view'];
            $options->message = $view->fetch($options['template'], $vars);
        } else {
            if (isset($options['body']) && !isset($options['message'])) {
                $options['message'] = $options['body'];
                unset($options['body']);
            }
        }

        // Append the system name as a prefix to the message.
        if (!isset($options['no_prefix']) || $options['no_prefix'] == false) {
            $app_name = $config->application->name;
            $options['subject'] = $app_name . ': ' . $options['subject'];
        }

        $mail_config = $config->application->mail->toArray();

        // Do not deliver mail on development environments.
        if (!APP_IN_PRODUCTION && !defined('APP_FORCE_EMAIL')) {
            $email_to = $mail_config['from_addr'];
            if (!empty($email_to)) {
                $options['to'] = $email_to;
            } else {
                return false;
            }
        }

        if (isset($mail_config['use_smtp']) && $mail_config['use_smtp']) {
            $smtp_config = $config->apis->smtp->toArray();
            $smtp_config['host'] = $smtp_config['server'];
            unset($smtp_config['server']);

            $transport = new SmtpMailer($smtp_config);
        } else {
            $transport = new SendmailMailer();
        }

        if (!is_array($options['to'])) {
            $options['to'] = [$options['to']];
        } else {
            $options['to'] = array_unique($options['to']);
        }

        foreach ((array)$options['to'] as $mail_to_addr) {
            if (empty($mail_to_addr)) {
                continue;
            }

            $mail_to_addr = str_replace('mailto:', '', $mail_to_addr);

            $mail = new Message;
            $mail->setSubject($options['subject']);

            $from_addr = (isset($options['from'])) ? $options['from'] : $mail_config['from_addr'];
            $from_name = (isset($options['from_name'])) ? $options['from_name'] : $mail_config['from_name'];
            $mail->setFrom($from_addr, $from_name);

            if (isset($mail_config['bounce_addr'])) {
                $mail->setReturnPath($mail_config['bounce_addr']);
            }

            // Change the type of the e-mail's body if specified in the options.
            if (isset($options['text_only']) && $options['text_only']) {
                $mail->setBody(strip_tags($options['message']));
            } else {
                $mail->setHtmlBody($options['message'], false);
            }

            // Add attachment if specified in options.
            if (isset($options['attachments'])) {
                foreach ((array)$options['attachments'] as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }

            // Catch invalid e-mails.
            try {
                $mail->addTo($mail_to_addr);
            } catch (\Nette\Utils\AssertionException $e) {
                continue;
            }

            $transport->send($mail);
        }

        return true;
    }
}