<?php
namespace Entity;

/**
 * @Table(name="api_keys")
 * @Entity
 */
class ApiKey
{
    /**
     * @Column(name="id", type="string", length=50)
     * @Id
     * @var string
     */
    protected $id;

    /**
     * @Column(name="owner", type="string", length=150, nullable=true)
     * @var string
     */
    protected $owner;

    /**
     * @Column(name="calls_made", type="integer")
     * @var int
     */
    protected $calls_made;

    /**
     * @Column(name="created", type="integer")
     * @var int
     */
    protected $created;

    /**
     * ApiKey constructor.
     * @param $owner
     */
    public function __construct(string $owner)
    {
        $this->id = sha1(mt_rand(0, microtime(true)));
        $this->owner = $owner;

        $this->calls_made = 0;
        $this->created = time();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner(string $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getCallsMade(): int
    {
        return $this->calls_made;
    }

    /**
     * @param int $calls_made
     */
    public function setCallsMade(int $calls_made)
    {
        $this->calls_made = $calls_made;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }
}