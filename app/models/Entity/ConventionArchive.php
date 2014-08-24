<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="convention_archives")
 * @Entity
 * @HasLifecycleCallbacks
 */
class ConventionArchive extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->type = 'yt_video';
        $this->created_at = time();
        $this->synchronized_at = 0;
    }

    /**
     * Clear all related video items when a playlist is deleted.
     * @PreRemove
     */
    public function deleting()
    {
        if ($this->type == 'yt_playlist')
        {
            $em = self::getEntityManager();

            $em->createQuery('DELETE FROM Entity\ConventionArchive ca WHERE ca.playlist_id = :id')
                ->setParameter('id', $this->id)
                ->execute();
        }
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="convention_id", type="integer") */
    protected $convention_id;

    /** @Column(name="playlist_id", type="integer", nullable=true) */
    protected $playlist_id;

    /** @Column(name="type", type="string", length=50, nullable=true) */
    protected $type;

    public function isPlayable()
    {
        return self::typeIsPlayable($this->type);
    }

    /** @Column(name="folder", type="string", length=50, nullable=true) */
    protected $folder;

    /** @Column(name="name", type="string", length=400, nullable=true) */
    protected $name;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="web_url", type="string", length=250, nullable=true) */
    protected $web_url;

    /** @Column(name="thumbnail_url", type="string", length=250, nullable=true) */
    protected $thumbnail_url;

    /** @Column(name="created_at", type="integer") */
    protected $created_at;

    /** @Column(name="synchronized_at", type="integer") */
    protected $synchronized_at;

    /**
     * @ManyToOne(targetEntity="Convention", inversedBy="archives")
     * @JoinColumns({
     *   @JoinColumn(name="convention_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $convention;

    public function process()
    {
        \PVL\ConventionManager::process($this);
        $this->save();
    }

    /**
     * Static Functions
     */

    public static function getTypes()
    {
        return array(
            'yt_video'      => 'YouTube Video',
            'yt_playlist'   => 'YouTube Playlist',
        );
    }
    public static function getFolders()
    {
        return array(
            'pvl'           => 'Ponyville Live! Footage',
            'efn'           => 'Everfree Network Footage',
            'viper'         => 'VIPER Footage',
            'con'           => 'Convention-Supplied Footage',
            'thirdparty'    => 'Third-Party Footage',
        );
    }
    public static function typeIsPlayable($type)
    {
        $playable_types = array('yt_video');
        return in_array($type, $playable_types);
    }
}