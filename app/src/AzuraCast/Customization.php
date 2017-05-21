<?php
namespace AzuraCast;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Entity;

class Customization
{
    /** @var ContainerInterface */
    protected $di;

    /** @var Entity\User|null */
    protected $user;

    /** @var \App\Config */
    protected $config;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->user = $di['user'];
        $this->config = $di['config'];
    }

    /**
     * Get the user's custom time zone or the system default.
     *
     * @return string
     */
    public function getTimeZone()
    {
        if ($this->user !== null && !empty($this->user->timezone)) {
            return $this->user->timezone;
        } else {
            return date_default_timezone_get();
        }
    }

    /*
     * Locale Commands:
     * find /var/azuracast/www -type f \( -name '*.php' -or -name '*.phtml' \) -print > list
     * xgettext --files-from=list --language=PHP -o /var/azuracast/www/app/locale/default.pot
     *
     * find /var/azuracast/www/app/locale -name \*.po -execdir msgfmt default.po -o default.mo \;
     */

    /**
     * Return the user-customized, browser-specified or system default locale.
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = null;
        $supported_locales = $this->config->application->locale->supported->toArray();

        // Prefer user-based profile locale.
        if ($this->user !== null && !empty($this->user->locale) && $this->user->locale !== 'default') {
            if (isset($supported_locales[$this->user->locale])) {
                $locale = $this->user->locale;
            }
        }

        // Attempt to load from browser headers.
        if (!$locale) {
            $browser_locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

            foreach ($supported_locales as $lang_code => $lang_name) {
                if (strcmp(substr($browser_locale, 0, 2), substr($lang_code, 0, 2)) == 0) {
                    $locale = $lang_code;
                    break;
                }
            }
        }

        // Default to system option.
        if (!$locale) {
            $locale = $this->config->application->locale->default;
        }

        return $locale;
    }

    /**
     * Returns the user-customized or system default theme.
     *
     * @return string
     */
    public function getTheme()
    {
        if ($this->user !== null && !empty($this->user->theme)) {
            $available_themes = $this->config->application->themes->available->toArray();
            if (isset($available_themes[$this->user->theme])) {
                return $this->user->theme;
            }
        }

        return $this->config->application->themes->default;
    }

    /**
     * Get the instance name for this AzuraCast instance.
     *
     * @return string|null
     */
    public function getInstanceName()
    {
        static $instance_name;

        if ($instance_name === null) {
            /** @var EntityManager $em */
            $em = $this->di['em'];

            /** @var Entity\Repository\SettingsRepository $settings_repo */
            $settings_repo = $em->getRepository(Entity\Settings::class);

            $instance_name = $settings_repo->getSetting('instance_name', '');
        }

        return $instance_name;
    }
}