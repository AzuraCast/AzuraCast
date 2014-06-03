<?php
/**
 * Messenger Class (E-mail Delivery)
 */

namespace DF;
class Messenger
{
	/**
	 * New messenger method.
	 * Uses an expandable array of options and supports direct template rendering and subject prepending.
	 */
	public static function send($message_options)
	{
        $config = \Zend_Registry::get('config');
		
		$default_options = array(
			'reply_to'			=> NULL,
			'delivery_date' 	=> NULL,
			'options'			=> NULL,
		);
		$options = array_merge($default_options, $message_options);
		
		// Render the template as the message if a template is specified.
		if (isset($options['template']))
		{
			$layout = new \Zend_Layout();
			$layout->setLayoutPath($config->application->resources->layout->layoutPath);
			$layout->setLayout('message');
			
			$view_renderer = Application\Bootstrap::getNewView(FALSE);
            $view = $view_renderer->view;
            
            if (isset($options['module']))
                $view_script_path = DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$options['module'].DIRECTORY_SEPARATOR.'messages';
            else
                $view_script_path = $config->application->mail->templates;
            
            $view->setScriptPath($view_script_path);
            
            $view->subject = $options['subject'];
            $view->assign((array)$options['vars']);
            
			$layout->content = $view->render($options['template'].'.phtml');
            
			$options['message'] = $layout->render();
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
		
		self::sendMail($options);
	}
	
	// Deliver a message.
	public static function sendMail()
	{
        // Do not deliver mail on development environments.
        if (DF_APPLICATION_ENV == "development" && !defined('DF_FORCE_EMAIL'))
            return;
        
		// Allow support for legacy argument style or new style.
		$args = func_get_args();
		if (func_num_args() == 1)
		{
			$options = $args[0];
		}
		else
		{
			$options = array_merge(array(
				'to'		=> $args[0],
				'subject'	=> $args[1],
				'message'	=> $args[2],
				'reply_to'	=> $args[3],
				'delivery_date' => $args[4],
			), $args[5]);
		}
		
		$config = \Zend_Registry::get('config');
        $mail_config = $config->application->mail->toArray();
		
		$validator = new \Zend_Validate_EmailAddress();

		if (isset($mail_config['use_smtp']) && $mail_config['use_smtp'])
		{
			$smtp_config = $config->apis->smtp->toArray();
			$smtp_server = $smtp_config['server'];
			unset($smtp_config['server']);

			$transport = new \Zend_Mail_Transport_Smtp($smtp_server, $smtp_config);
		}
		else
		{
			$transport = new \Zend_Mail_Transport_Sendmail();
		}
		
		if (!is_array($options['to']))
			$options['to'] = array($options['to']);
		
		foreach((array)$options['to'] as $mail_to_addr)
		{
			if ($mail_to_addr && $validator->isValid($mail_to_addr))
			{
				$mail = new \Zend_Mail();
				$mail->setSubject($options['subject']);
				
				$from_addr = (isset($options['from'])) ? $options['from'] : $mail_config['from_addr'];
				$from_name = (isset($options['from_name'])) ? $options['from_name'] : $mail_config['from_name'];
                $mail->setFrom($from_addr, $from_name);
                
                if (isset($mail_config['bounce_addr']))
                    $mail->setReturnPath($mail_config['bounce_addr']);
				
				// Include a specific "Direct replies to" header if specified.
				if ($options['reply_to'] && $validator->isValid($options['reply_to']))
					$mail->setReplyTo($options['reply_to']);
				else if (isset($mail_config['reply_to']) && $mail_config['reply_to'])
					$mail->setReplyTo($mail_config['reply_to']);
								
				// Change the type of the e-mail's body if specified in the options.
				if (isset($options['text_only']) && $options['text_only'])
					$mail->setBodyText(strip_tags($options['message']));
				else
					$mail->setBodyHtml($options['message'], NULL, \Zend_Mime::ENCODING_8BIT);
				
				// Add attachment if specified in options.
				if (isset($options['attachments']))
				{
					foreach((array)$options['attachments'] as $attachment)
					{
						if ($attachment instanceof \Zend_Mime_Part)
							$mail->addAttachment($attachment);
						else
							$mail->addAttachment(self::attachFile($attachment));
					}
				}
				
				// Modify the mail type if specified.
				if (isset($options['type']))
					$mail->setType($options['type']);
				
				$mail->addTo($mail_to_addr);
				
				$mail->send($transport);
			}
		}
	}
	
	// Get a file attachment object for use in main messenging functions.
	public static function attachFile($file_path, $file_name = NULL, $mime_type = NULL)
	{		
		if (!file_exists($file_path))
			throw new Exception('File not found for attachment!');
		
		// Get the name of the file.
		$file_name = (!is_null($file_name)) ? $file_name : basename($file_path);
			
		// Compose the attachment object.
		$at = new \Zend_Mime_Part(file_get_contents($file_path));
		
		if (!is_null($mime_type))		
			$at->type        = $mime_type;
		
		$at->filename    = $file_name;
		$at->disposition = \Zend_Mime::DISPOSITION_INLINE;
		$at->encoding    = \Zend_Mime::ENCODING_BASE64;
		return $at;
	}
	
	// Attach an iCalendar invitation.
    public static function attachInvite($ics_data)
    {
        // Compose the attachment object.
        $at = new \Zend_Mime_Part($ics_data);
        $at->type        = 'text/calendar';
        $at->filename    = 'calendar.ics';
		$at->disposition = \Zend_Mime::DISPOSITION_INLINE;
		$at->encoding    = \Zend_Mime::ENCODING_8BIT;
		
		return $at;
	}
}