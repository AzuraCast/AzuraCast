<?php

declare(strict_types=1);

namespace App\Session;

use App\Environment;
use App\Exception\Http\CsrfValidationException;
use App\Utilities\Types;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Csrf
{
    public const int CODE_LENGTH = 10;
    public const string DEFAULT_NAMESPACE = 'general';

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly SessionInterface $session,
        private readonly Environment $environment
    ) {
    }

    /**
     * Generate a Cross-Site Request Forgery (CSRF) protection token.
     * The "namespace" allows distinct CSRF tokens for different site functions,
     * while not crowding the session namespace with one token for each action.
     * If not renewed (with another "generate" call to the same namespace),
     * a CSRF token will last the time specified in $this->_csrf_lifetime.
     *
     * @param string $namespace
     */
    public function generate(string $namespace = self::DEFAULT_NAMESPACE): string
    {
        $sessionKey = $this->getSessionIdentifier($namespace);
        if ($this->session->has($sessionKey)) {
            $csrf = Types::stringOrNull($this->session->get($sessionKey), true);
            if (null !== $csrf) {
                return $csrf;
            }
        }

        $key = $this->randomString();
        $this->session->set($sessionKey, $key);

        return $key;
    }

    /**
     * Verify a supplied CSRF token against the tokens stored in the session.
     *
     * @param string $key
     * @param string $namespace
     *
     * @throws CsrfValidationException
     */
    public function verify(string $key, string $namespace = self::DEFAULT_NAMESPACE): void
    {
        if ($this->environment->isTesting()) {
            return;
        }

        if (empty($key)) {
            throw new CsrfValidationException(
                $this->request,
                'A CSRF token is required for this request.'
            );
        }

        if (strlen($key) !== self::CODE_LENGTH) {
            throw new CsrfValidationException(
                $this->request,
                'Malformed CSRF token supplied.'
            );
        }

        $sessionIdentifier = $this->getSessionIdentifier($namespace);
        if (!$this->session->has($sessionIdentifier)) {
            throw new CsrfValidationException(
                $this->request,
                'No CSRF token supplied for this namespace.'
            );
        }

        $sessionKey = Types::string($this->session->get($sessionIdentifier));
        if (0 !== strcmp($key, $sessionKey)) {
            throw new CsrfValidationException(
                $this->request,
                'Invalid CSRF token supplied.'
            );
        }
    }

    /**
     * Generates a random string of given $length.
     *
     * @return string The randomly generated string.
     */
    private function randomString(): string
    {
        $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijqlmnopqrtsuvwxyz0123456789';
        $max = strlen($seed) - 1;

        $string = '';
        for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
            $string .= $seed[random_int(0, $max)];
        }

        return $string;
    }

    private function getSessionIdentifier(string $namespace): string
    {
        return 'csrf_' . $namespace;
    }
}
