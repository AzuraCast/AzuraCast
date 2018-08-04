<?php
namespace App\Session;

use App\Session;
use App\Exception;

class Csrf
{
    /**
     * @var \App\Session\Instance
     */
    protected $_session;

    /**
     * @var int The length of the string to generate.
     */
    protected $_csrf_code_length = 10;

    /**
     * @var int The lifetime (in seconds) of an un-renewed CSRF token.
     */
    protected $_csrf_lifetime = 3600;

    /**
     * @var string The default namespace used for token generation.
     */
    protected $_csrf_default_namespace = 'general';

    public function __construct(Session $session)
    {
        $this->_session = $session->get('csrf');
    }

    /**
     * Generate a Cross-Site Request Forgery (CSRF) protection token.
     * The "namespace" allows distinct CSRF tokens for different site functions,
     * while not crowding the session namespace with one token for each action.
     *
     * If not renewed (with another "generate" call to the same namespace),
     * a CSRF token will last the time specified in $this->_csrf_lifetime.
     *
     * @param string $namespace
     * @return null|String
     */
    public function generate($namespace = null)
    {
        if ($namespace === null) {
            $namespace = $this->_csrf_default_namespace;
        }

        $key = null;
        if (isset($this->_session[$namespace])) {
            $key = $this->_session[$namespace]['key'];
            if (strlen($key) !== $this->_csrf_code_length) {
                $key = null;
            }
        }

        if (!$key) {
            $key = $this->randomString($this->_csrf_code_length);
        }

        $this->_session[$namespace] = [
            'key' => $key,
            'timestamp' => time(),
        ];

        return $key;
    }

    /**
     * Verify a supplied CSRF token against the tokens stored in the session.
     *
     * @param $key
     * @param string $namespace
     * @throws Exception\CsrfValidation
     */
    public function verify($key, $namespace = null): void
    {
        if ($namespace === null) {
            $namespace = $this->_csrf_default_namespace;
        }

        if (empty($key)) {
            throw new Exception\CsrfValidation('A CSRF token is required for this request.');
        }

        if (strlen($key) !== $this->_csrf_code_length) {
            throw new Exception\CsrfValidation('Malformed CSRF token supplied.');
        }

        if (!isset($this->_session[$namespace])) {
            throw new Exception\CsrfValidation('No CSRF token supplied for this namespace.');
        }

        $namespace_info = $this->_session[$namespace];

        if (strcmp($key, $namespace_info['key']) !== 0) {
            throw new Exception\CsrfValidation('Invalid CSRF token supplied.');
        }

        // Compare against time threshold (CSRF keys last 60 minutes).
        $threshold = $namespace_info['timestamp'] + $this->_csrf_lifetime;

        if (time() >= $threshold) {
            throw new Exception\CsrfValidation('This CSRF token has expired.');
        }
    }

    /**
     * Generates a random string of given $length.
     *
     * @param Integer $length The string length.
     * @return String The randomly generated string.
     */
    public function randomString($length)
    {
        $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijqlmnopqrtsuvwxyz0123456789';
        $max = strlen($seed) - 1;

        $string = '';
        for ($i = 0; $i < $length; ++$i) {
            $string .= $seed{random_int(0, $max)};
        }

        return $string;
    }
}
