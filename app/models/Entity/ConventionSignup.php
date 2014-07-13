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

    /** @Column(name="user_id", type="integer") */
    protected $user_id;

    /** @Column(name="pony_name", type="string", length=400, nullable=true) */
    protected $pony_name;

    /** @Column(name="legal_name", type="string", length=400, nullable=true) */
    protected $legal_name;

    /** @Column(name="phone", type="string", length=50, nullable=true) */
    protected $phone;

    /** @Column(name="pvl_affiliation", type="string", length=50, nullable=true) */
    protected $pvl_affiliation;

    /** @Column(name="travel_notes", type="text", nullable=true) */
    protected $travel_notes;

    /** @Column(name="accommodation_notes", type="text", nullable=true) */
    protected $accommodation_notes;

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
     *   @JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     */
    protected $user;
}