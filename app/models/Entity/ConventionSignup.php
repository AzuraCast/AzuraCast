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

    /** @Column(name="email", type="string", length=100, nullable=true) */
    protected $email;

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

    /**
     * Static Functions
     */

    /**
     * Generate a customized form instance for the specified convention.
     *
     * @param Convention $con
     * @return \DF\Form
     */
    public static function getForm(Convention $con)
    {
        $di = \Phalcon\Di::getDefault();
        $module_config = $di->get('module_config');

        $form_config = $module_config['admin']->forms->conventionsignup->toArray();

        $con_info = array(
            '<big>'.$con->name.'</big>',
            $con->location,
            $con->getRange(),
        );

        $form_config['groups']['con_info']['elements']['about_con'][1]['markup'] = '<div>'.implode('</div><div>', $con_info).'</div>';

        if ($con->signup_notes)
        {
            $form_config['groups']['con_info']['elements']['special_con_notes'][1]['markup'] = '<p>'.nl2br($con->signup_notes).'</p>';
        }
        else
        {
            unset($form_config['groups']['con_info']['elements']['special_con_notes']);
        }

        return new \DF\Form($form_config);
    }
}