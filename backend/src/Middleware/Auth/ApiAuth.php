<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Acl;
use App\Auth;
use App\Customization;
use App\Entity\Repository\ApiKeyRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\Http\CsrfValidationException;
use App\Http\ServerRequest;
use App\Security\SplitToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApiAuth extends AbstractAuth
{
    public const string API_CSRF_NAMESPACE = 'api';

    public function __construct(
        protected ApiKeyRepository $apiKeyRepo,
        UserRepository $userRepo,
        Acl $acl,
        Customization $customization
    ) {
        parent::__construct($userRepo, $acl, $customization);
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Initialize the Auth for this request.
        $user = $this->getApiUser($request);

        $request = $request->withAttribute(ServerRequest::ATTR_USER, $user);

        return parent::__invoke($request, $handler);
    }

    private function getApiUser(ServerRequest $request): ?User
    {
        $apiKey = $this->getApiKey($request);

        if (!empty($apiKey)) {
            $apiRecord = $this->apiKeyRepo->authenticate($apiKey);
            if (null !== $apiRecord) {
                return $apiRecord->getUser();
            }
        }

        // Fallback to session login if available.
        $auth = new Auth(
            userRepo: $this->userRepo,
            session: $request->getSession(),
        );
        $auth->setEnvironment($this->environment);

        if ($auth->isLoggedIn()) {
            $user = $auth->getLoggedInUser();
            if ('GET' === $request->getMethod()) {
                return $user;
            }

            $csrfKey = $request->getHeaderLine('X-API-CSRF');
            if (empty($csrfKey) && !$this->environment->isTesting()) {
                return null;
            }

            $csrf = $request->getCsrf();
            try {
                $csrf->verify($csrfKey, self::API_CSRF_NAMESPACE);
                return $user;
            } catch (CsrfValidationException) {
            }
        }

        return null;
    }

    private function getApiKey(ServerRequestInterface $request): ?string
    {
        // Check authorization header
        $authHeaders = $request->getHeader('Authorization');
        $authHeader = $authHeaders[0] ?? '';

        if (preg_match("/Bearer\s+(.*)$/i", $authHeader, $matches)) {
            $apiKey = $matches[1];
            if (SplitToken::isValidKeyString($apiKey)) {
                return $apiKey;
            }
        }

        // Check API key header
        $apiKeyHeaders = $request->getHeader('X-API-Key');
        if (!empty($apiKeyHeaders[0]) && SplitToken::isValidKeyString($apiKeyHeaders[0])) {
            return $apiKeyHeaders[0];
        }

        // Check cookies
        $cookieParams = $request->getCookieParams();
        if (!empty($cookieParams['token']) && SplitToken::isValidKeyString($cookieParams['token'])) {
            return $cookieParams['token'];
        }

        // Check URL parameters as last resort
        $queryParams = $request->getQueryParams();
        $queryApiKey = $queryParams['api_key'] ?? null;
        if (!empty($queryApiKey) && SplitToken::isValidKeyString($queryApiKey)) {
            return $queryApiKey;
        }

        return null;
    }
}
