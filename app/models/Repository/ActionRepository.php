<?php
namespace Repository;

use App\Doctrine\Repository;

class ActionRepository extends Repository
{
    public function getUsersWithAction($action_name)
    {
        return $this->_em->createQuery('SELECT u FROM Entity\User u LEFT JOIN u.roles r LEFT JOIN r.actions a WHERE (a.name = :action OR a.name = :admin_action)')
            ->setParameter('action', $action_name)
            ->setParameter('admin_action', 'administer all')
            ->getArrayResult();
    }
}