<?php

declare(strict_types=1);

namespace App\Http;

use App\Acl;
use App\Auth;
use App\Customization;
use App\Entity;
use App\Exception;
use App\Locale;
use App\Radio;
use App\RateLimit;
use App\Session;
use App\View;
use Mezzio\Session\SessionInterface;

final class ServerRequest extends \Slim\Http\ServerRequest
{
    public const ATTR_VIEW = 'app_view';
    public const ATTR_SESSION = 'app_session';
    public const ATTR_SESSION_CSRF = 'app_session_csrf';
    public const ATTR_SESSION_FLASH = 'app_session_flash';
    public const ATTR_ROUTER = 'app_router';
    public const ATTR_RATE_LIMIT = 'app_rate_limit';
    public const ATTR_ACL = 'acl';
    public const ATTR_LOCALE = 'locale';
    public const ATTR_CUSTOMIZATION = 'customization';
    public const ATTR_AUTH = 'auth';
    public const ATTR_STATION = 'station';
    public const ATTR_STATION_BACKEND = 'station_backend';
    public const ATTR_STATION_FRONTEND = 'station_frontend';
    public const ATTR_STATION_REMOTES = 'station_remotes';
    public const ATTR_USER = 'user';

    public function getView(): View
    {
        return $this->getAttributeOfClass(self::ATTR_VIEW, View::class);
    }

    public function getSession(): SessionInterface
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION, SessionInterface::class);
    }

    public function getCsrf(): Session\Csrf
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_CSRF, Session\Csrf::class);
    }

    public function getFlash(): Session\Flash
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_FLASH, Session\Flash::class);
    }

    public function getRouter(): RouterInterface
    {
        return $this->getAttributeOfClass(self::ATTR_ROUTER, RouterInterface::class);
    }

    public function getRateLimit(): RateLimit
    {
        return $this->getAttributeOfClass(self::ATTR_RATE_LIMIT, RateLimit::class);
    }

    public function getLocale(): Locale
    {
        return $this->getAttributeOfClass(self::ATTR_LOCALE, Locale::class);
    }

    public function getCustomization(): Customization
    {
        return $this->getAttributeOfClass(self::ATTR_CUSTOMIZATION, Customization::class);
    }

    public function getAuth(): Auth
    {
        return $this->getAttributeOfClass(self::ATTR_AUTH, Auth::class);
    }

    public function getAcl(): Acl
    {
        return $this->getAttributeOfClass(self::ATTR_ACL, Acl::class);
    }

    public function getUser(): Entity\User
    {
        return $this->getAttributeOfClass(self::ATTR_USER, Entity\User::class);
    }

    public function getStation(): Entity\Station
    {
        return $this->getAttributeOfClass(self::ATTR_STATION, Entity\Station::class);
    }

    public function getStationFrontend(): Radio\Frontend\AbstractFrontend
    {
        return $this->getAttributeOfClass(self::ATTR_STATION_FRONTEND, Radio\Frontend\AbstractFrontend::class);
    }

    public function getStationBackend(): Radio\Backend\AbstractBackend
    {
        return $this->getAttributeOfClass(self::ATTR_STATION_BACKEND, Radio\Backend\AbstractBackend::class);
    }

    /**
     * @return Radio\Remote\AdapterProxy[]
     * @throws Exception\InvalidRequestAttribute
     */
    public function getStationRemotes(): array
    {
        $remotes = $this->serverRequest->getAttribute(self::ATTR_STATION_REMOTES);

        if (null === $remotes) {
            throw new Exception\InvalidRequestAttribute(sprintf(
                'Attribute "%s" was not set.',
                self::ATTR_STATION_REMOTES
            ));
        }

        return $remotes;
    }

    /**
     * @param string $attr
     * @param string $class_name
     *
     * @throws Exception\InvalidRequestAttribute
     */
    private function getAttributeOfClass(string $attr, string $class_name): mixed
    {
        $object = $this->serverRequest->getAttribute($attr);

        if (empty($object)) {
            throw new Exception\InvalidRequestAttribute(
                sprintf(
                    'Attribute "%s" is required and is empty in this request',
                    $attr
                )
            );
        }

        if (!($object instanceof $class_name)) {
            throw new Exception\InvalidRequestAttribute(
                sprintf(
                    'Attribute "%s" must be of type "%s".',
                    $attr,
                    $class_name
                )
            );
        }

        return $object;
    }

    /**
     * Get the remote user's IP address as indicated by HTTP headers.
     */
    public function getIp(): string
    {
        $params = $this->serverRequest->getServerParams();

        $ip = $params['HTTP_CLIENT_IP']
            ?? $params['HTTP_X_FORWARDED_FOR']
            ?? $params['HTTP_X_FORWARDED']
            ?? $params['HTTP_FORWARDED_FOR']
            ?? $params['HTTP_FORWARDED']
            ?? $params['REMOTE_ADDR']
            ?? null;

        if (null === $ip) {
            throw new \RuntimeException('No IP address attached to this request.');
        }

        // Handle the IP being separated by commas.
        $ipParts = explode(',', $ip);
        $ip = array_shift($ipParts);

        return trim($ip);
    }
}
