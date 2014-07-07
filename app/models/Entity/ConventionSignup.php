<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="convention_signups")
 * @Entity
 */
class ConventionSignup extends \DF\Doctrine\Entity
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="convention_id", type="integer") */
    protected $convention_id;

    /** @Column(name="user_id", type="integer", nullable=true) */
    protected $user_id;

    /** @Column(name="name", type="string", length=400, nullable=true) */
    protected $name;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="web_url", type="string", length=250, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=200, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        if ($new_url)
        {
            if ($this->image_url && $this->image_url != $new_url)
                @unlink(DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$this->image_url);

            $new_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_url;
            \DF\Image::resizeImage($new_path, $new_path, 1150, 200);

            $this->image_url = $new_url;
        }
    }

    /**
     * @ManyToOne(targetEntity="Convention", inversedBy="signups")
     * @JoinColumns({
     *   @JoinColumn(name="convention_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $convention;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $user;
}