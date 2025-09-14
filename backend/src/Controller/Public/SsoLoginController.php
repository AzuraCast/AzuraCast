<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\SsoService;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class SsoLoginController
{
    public function __construct(
        private readonly SsoService $ssoService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Handle SSO login with token.
     */
    public function login(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $token = $request->getQueryParam('token');
            if (empty($token)) {
                return $this->renderError($request, $response, 'SSO token is required', [], 400);
            }

            // Validate and consume the token
            $user = $this->ssoService->validateAndConsumeToken($token);
            if (!$user) {
                return $this->renderError($request, $response, 'Invalid or expired SSO token', [], 401);
            }

            // Log the user in by setting session data
            $session = $request->getAttribute(ServerRequest::ATTR_SESSION);
            if ($session instanceof SessionInterface) {
                $session->set('user_id', $user->id);
                // Set is_login_complete based on whether user has 2FA enabled
                $isLoginComplete = null === $user->two_factor_secret;
                $session->set('is_login_complete', $isLoginComplete);
                // Note: Session regeneration removed temporarily to avoid timing issues
            }

            // Redirect to dashboard or specified redirect URL
            $redirectUrl = $request->getQueryParam('redirect', '/dashboard');
            
            // Validate redirect URL to prevent open redirects
            if (!$this->isValidRedirectUrl($redirectUrl)) {
                $redirectUrl = '/dashboard';
            }

            $this->logger->info('SSO login: Redirecting to URL', [
                'redirect_url' => $redirectUrl,
                'user_id' => $user->id,
            ]);

            return $response->withRedirect($redirectUrl);
        } catch (\Exception $e) {
            $this->logger->error('SSO login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->renderError($request, $response, 'SSO login failed', [], 500);
        }
    }

    /**
     * Validate redirect URL to prevent open redirects.
     */
    private function isValidRedirectUrl(string $url): bool
    {
        // Allow relative URLs
        if (str_starts_with($url, '/')) {
            return true;
        }

        // Allow same-origin URLs
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }

        // For now, only allow relative URLs for security
        return false;
    }

    /**
     * Render error page for SSO login failures.
     */
    private function renderError(ServerRequest $request, Response $response, string $message, array $details = [], int $status = 400): ResponseInterface
    {
        $errorData = [
            'success' => false,
            'error' => $message,
            'details' => $details,
        ];

        return $response->withStatus($status)->withJson($errorData);
    }
}
