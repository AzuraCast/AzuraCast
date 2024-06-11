<?php

declare(strict_types=1);

namespace App\Http;

use App\Acl;
use App\Auth;
use App\Customization;
use App\Entity\Podcast;
use App\Entity\Station;
use App\Entity\User;
use App\Enums\SupportedLocales;
use App\Exception\Http\InvalidRequestAttribute;
use App\RateLimit;
use App\Session;
use App\Utilities\Types;
use App\View;
use Mezzio\Session\SessionInterface;
use Slim\Http\ServerRequest as SlimServerRequest;

final class ServerRequest extends SlimServerRequest
{
    public const string ATTR_IS_API = 'is_api';
    public const string ATTR_VIEW = 'app_view';
    public const string ATTR_SESSION = 'app_session';
    public const string ATTR_SESSION_CSRF = 'app_session_csrf';
    public const string ATTR_SESSION_FLASH = 'app_session_flash';
    public const string ATTR_ROUTER = 'app_router';
    public const string ATTR_RATE_LIMIT = 'app_rate_limit';
    public const string ATTR_ACL = 'acl';
    public const string ATTR_LOCALE = 'locale';
    public const string ATTR_CUSTOMIZATION = 'customization';
    public const string ATTR_AUTH = 'auth';
    public const string ATTR_STATION = 'station';
    public const string ATTR_PODCAST = 'podcast';
    public const string ATTR_USER = 'user';

    /**
     * @throws InvalidRequestAttribute
     */
    public function getView(): View
    {
        return $this->getAttributeOfClass(self::ATTR_VIEW, View::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getSession(): SessionInterface
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION, SessionInterface::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getCsrf(): Session\Csrf
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_CSRF, Session\Csrf::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getFlash(): Session\Flash
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_FLASH, Session\Flash::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getRouter(): RouterInterface
    {
        return $this->getAttributeOfClass(self::ATTR_ROUTER, RouterInterface::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getRateLimit(): RateLimit
    {
        return $this->getAttributeOfClass(self::ATTR_RATE_LIMIT, RateLimit::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getLocale(): SupportedLocales
    {
        return $this->getAttributeOfClass(self::ATTR_LOCALE, SupportedLocales::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getCustomization(): Customization
    {
        return $this->getAttributeOfClass(self::ATTR_CUSTOMIZATION, Customization::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getAuth(): Auth
    {
        return $this->getAttributeOfClass(self::ATTR_AUTH, Auth::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getAcl(): Acl
    {
        return $this->getAttributeOfClass(self::ATTR_ACL, Acl::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getUser(): User
    {
        return $this->getAttributeOfClass(self::ATTR_USER, User::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getStation(): Station
    {
        return $this->getAttributeOfClass(self::ATTR_STATION, Station::class);
    }

    /**
     * @throws InvalidRequestAttribute
     */
    public function getPodcast(): Podcast
    {
        return $this->getAttributeOfClass(self::ATTR_PODCAST, Podcast::class);
    }

    public function isApi(): bool
    {
        return Types::bool($this->getAttribute(self::ATTR_IS_API));
    }

    /**
     * @template T of object
     *
     * @param string $attr
     * @param class-string<T> $className
     * @return T
     *
     * @throws InvalidRequestAttribute
     */
    private function getAttributeOfClass(string $attr, string $className): object
    {
        $object = $this->serverRequest->getAttribute($attr);

        if (empty($object)) {
            throw new InvalidRequestAttribute(
                $this,
                sprintf(
                    'Attribute "%s" is required and is empty in this request',
                    $attr
                )
            );
        }

        if (!($object instanceof $className)) {
            throw new InvalidRequestAttribute(
                $this,
                sprintf(
                    'Attribute "%s" must be of type "%s".',
                    $attr,
                    $className
                )
            );
        }

        return $object;
    }

    public function isInternal(): bool
    {
        return Types::bool(
            $this->getParam('internal', false),
            false,
            true
        );
    }
}
