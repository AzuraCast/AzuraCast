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
     * @Column(name="provider", type="string", length=255)
     * @Id
     */
    protected $provider;

    /**
     * @Column(name="external_id", type="string", length=255)
     * @Id
     */
    protected $external_id;

    /** @Column(name="user_id", type="integer") */
    protected $user_id;

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

    public static function processExternal($provider, $user_profile)
    {
        $external = self::getRepository()->findOneBy(array('provider' => $provider, 'external_id' => $user_profile->identifier));

        if ($external instanceof self)
        {
            return $external->user;
        }
        else
        {
            // Find or create user account.
            $user = User::getRepository()->findOneBy(array('email' => $email));

            if (!($user instanceof User)) {
                $user = new User;
                $user->email = $email;
                $user->name = $user_profile->displayName;
                $user->avatar_url = $user_profile->photoURL;
                $user->generateRandomPassword();
                $user->save();
            }

            if (!($external instanceof self)) {
                $external = new self;
                $external->provider = $provider;
                $external->external_id = $user_profile->identifier;

                $external->user = $user;
                $external->avatar_url = $user_profile->photoURL;
                $external->save();
            }

            return $user;
        }
    }
}
