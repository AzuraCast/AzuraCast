<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Controller\Api\AbstractGenericCrudController;

/**
 * @see \App\Provider\ApiProvider
 */
class UsersController extends AbstractGenericCrudController
{
    protected $entityClass = Entity\User::class;
    protected $resourceRouteName = 'api:admin:user';
}
