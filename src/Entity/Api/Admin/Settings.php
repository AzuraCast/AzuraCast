<?php

namespace App\Entity\Api\Admin;

use App\Entity;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(type="object", schema="Api_Admin_Settings")
 */
class Settings
{
    /**
     * Site Base URL
     *
     * @Assert\NotBlank
     * @OA\Property()
     * @var string
     */
    public string $base_url;

    /**
     * AzuraCast Instance Name
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $instance_name;

    /**
     * System Default Time Zone
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $timezone = 'UTC';

    /**
     * Prefer Browser URL (If Available)
     *
     * @OA\Property()
     * @Assert\Choice({0,1})
     * @var int
     */
    public int $prefer_browser_url = 0;
    /**
     * Use Web Proxy for Radio
     *
     * @OA\Property()
     * @Assert\Choice({0,1})
     * @var int
     */
    public int $use_radio_proxy = 0;
    /**
     * Days of Playback History to Keep
     *
     * @OA\Property()
     * @Assert\Choice({0,14,30,60,365,730})
     * @var int
     */
    public int $history_keep_days = Entity\SongHistory::DEFAULT_DAYS_TO_KEEP;
    /**
     * Always Use HTTPS
     *
     * @OA\Property()
     * @Assert\Choice({0,1})
     * @var int
     */
    public int $always_use_ssl = 0;
    /**
     * API "Access-Control-Allow-Origin" header
     *
     * @OA\Property()
     * @var string
     */
    public string $api_access_control;
    /**
     * Listener Analytics Collection
     *
     * @OA\Property()
     * @Assert\Choice({Entity\Analytics::LEVEL_NONE, Entity\Analytics::LEVEL_NO_IP, Entity\Analytics::LEVEL_ALL})
     * @var string
     */
    public string $analytics = Entity\Analytics::LEVEL_ALL;
    /**
     * Check for Updates and Announcements
     *
     * @OA\Property()
     * @Assert\Choice({
     *     Entity\Settings::UPDATES_NONE,
     *     Entity\Settings::UPDATES_RELEASE_ONLY,
     *     Entity\Settings::UPDATES_ALL
     * })
     * @var int
     */
    public int $central_updates_channel = Entity\Settings::UPDATES_RELEASE_ONLY;
    /**
     * Base Theme for Public Pages
     *
     * @OA\Property()
     * @Assert\Choice({"light", "dark"})
     * @var string
     */
    public string $public_theme = 'light';
    /**
     * Hide Album Art on Public Pages
     *
     * @OA\Property()
     * @Assert\Choice({0,1})
     * @var int
     */
    public int $hide_album_art = 0;
    /**
     * Homepage Redirect URL
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $homepage_redirect_url;
    /**
     * Default Album Art URL
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $default_album_art_url;
    /**
     * Hide AzuraCast Branding on Public Pages
     *
     * @OA\Property()
     * @Assert\Choice({0,1})
     * @var int
     */
    public int $hide_product_name = 0;
    /**
     * Custom CSS for Public Pages
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $custom_css_public;
    /**
     * Custom JS for Public Pages
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $custom_js_public;
    /**
     * Custom CSS for Internal Pages
     *
     * @OA\Property()
     * @var string|null
     */
    public ?string $custom_css_internal;

    /**
     * @param int $prefer_browser_url
     */
    public function setPreferBrowserUrl(int $prefer_browser_url): void
    {
        $this->prefer_browser_url = $prefer_browser_url;
    }

    /**
     * @param int $use_radio_proxy
     */
    public function setUseRadioProxy(int $use_radio_proxy): void
    {
        $this->use_radio_proxy = $use_radio_proxy;
    }

    /**
     * @param int $history_keep_days
     */
    public function setHistoryKeepDays(int $history_keep_days): void
    {
        $this->history_keep_days = $history_keep_days;
    }

    /**
     * @param int $always_use_ssl
     */
    public function setAlwaysUseSsl(int $always_use_ssl): void
    {
        $this->always_use_ssl = $always_use_ssl;
    }

    /**
     * @param int $central_updates_channel
     */
    public function setCentralUpdatesChannel(int $central_updates_channel): void
    {
        $this->central_updates_channel = $central_updates_channel;
    }

    /**
     * @param int $hide_album_art
     */
    public function setHideAlbumArt(int $hide_album_art): void
    {
        $this->hide_album_art = $hide_album_art;
    }

    /**
     * @param int $hide_product_name
     */
    public function setHideProductName(int $hide_product_name): void
    {
        $this->hide_product_name = $hide_product_name;
    }
}
