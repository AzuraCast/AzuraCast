<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="api_calls", indexes={
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class ApiCall extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->client = 'general';
        $this->timestamp = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="ip", type="string", length=45) */
    protected $ip;

    /** @Column(name="client", type="string", length=45, nullable=true) */
    protected $client;

    /** @Column(name="useragent", type="string", length=255, nullable=true) */
    protected $useragent;

    /** @Column(name="controller", type="string", length=150) */
    protected $controller;

    /** @Column(name="action", type="string", length=150) */
    protected $action;

    /** @Column(name="parameters", type="json", nullable=true) */
    protected $parameters;

    /** @Column(name="requesttime", type="float") */
    protected $requesttime;

    /**
     * Static Functions
     */

    public static function cleanUp()
    {
        $em = self::getEntityManager();

        $threshold = strtotime('-7 days');

        $em->createQuery('DELETE FROM ApiCall WHERE timestamp <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();

        return true;
    }
}