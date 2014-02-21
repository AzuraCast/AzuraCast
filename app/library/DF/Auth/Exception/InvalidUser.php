<?php
/** 
 * Invalid User exception
 */

namespace DF\Auth\Exception;
class InvalidUser extends \DF\Exception\DisplayOnly {
	public function __construct($message = NULL, $code = 0, Exception $previous = null) {
		if (!$message)
			$message = 'Your account has experienced an error and has been logged out for security purposes. Please log in again to continue.';
		
        parent::__construct($message, $code, $previous);
    }
}