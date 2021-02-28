<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_login_tokens")
 * @ORM\Entity(readOnly=true)
 */
class UserLoginToken
{
    use Traits\HasSplitTokenFields;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="api_keys", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(name="created_at", type="integer")
     * @var int
     */
    protected $created_at;

    public function __construct(User $user, SplitToken $token)
    {
        $this->user = $user;
        $this->setFromToken($token);
        $this->created_at = time();
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
