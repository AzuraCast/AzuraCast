<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="users_external")
 * @Entity
 */
class UserExternal extends \DF\Doctrine\Entity
{
    public function __construct()
    {}

    /**
     * @Column(name="provider", type="string", length=50)
     * @Id
     */
    protected $provider;

    /**
     * @Column(name="external_id", type="string", length=100)
     * @Id
     */
    protected $external_id;

    /** @Column(name="user_id", type="integer", nullable=true) */
    protected $user_id;

    /** @Column(name="name", type="string", length=255, nullable=true) */
    protected $name;

    public function getName()
    {
        if ($this->name)
            return $this->name;
        else
            return $this->user->name;
    }

    /** @Column(name="avatar_url", type="string", length=255, nullable=true) */
    protected $avatar_url;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="external_accounts")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     */
    protected $user;

    /**
     * Static Functions
     */

    public static function processExternal($provider, $user_profile, User $user = null)
    {
        $external = self::getRepository()->findOneBy(array('provider' => $provider, 'external_id' => $user_profile->identifier));

        // Locate a user account to associate.
        if ($user instanceof User)
        {
            // No additional processing.
        }
        elseif ($external instanceof self && $external->user instanceof User)
        {
            $user = $external->user;
        }
        elseif (!empty($user_profile->email))
        {
            $user = User::getRepository()->findOneBy(array('email' => $user_profile->email));

            if (!($user instanceof User))
            {
                $user = new User;
                $user->email = $user_profile->email;
                $user->name = $user_profile->displayName;
                $user->avatar_url = $user_profile->photoURL;
                $user->generateRandomPassword();
                $user->save();
            }
        }
        else
        {
            // Not enough information to auto-create account; throw exception.
            throw new \PVL\Exception\AccountNotLinked;
        }

        // Create new external record (if none exists)
        if (!($external instanceof self))
        {
            // Create new external account and associate with the specified user.
            $external = new self;
            $external->provider = $provider;
            $external->external_id = $user_profile->identifier;
        }

        $external->user = $user;
        $external->name = $user_profile->displayName;
        $external->avatar_url = $user_profile->photoURL;
        $external->save();

        return $user;
    }

    public static function getExternalProviders()
    {
        return array(
            'facebook' => array(
                'name'      => 'Facebook',
                'class'     => 'facebook',
                'icon'      => 'icon-facebook',
            ),
            'google' => array(
                'name'      => 'Google+',
                'class'     => 'googleplus',
                'icon'      => 'icon-google-plus',
            ),
            'twitter' => array(
                'name'      => 'Twitter',
                'class'     => 'twitter',
                'icon'      => 'icon-twitter',
            ),
            'tumblr' => array(
                'name'      => 'Tumblr',
                'class'     => 'tumblr',
                'icon'      => 'icon-tumblr',
            ),
            'poniverse' => array(
                'name'      => 'Poniverse.net',
                'class'     => 'guest',
                'icon'      => 'icon-user',
            ),
        );
    }
}
