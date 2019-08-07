<?php
namespace App\Controller\Admin;

use App\Form\EntityForm;
use App\Http\RequestHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractAdminCrudController
{
    /** @var EntityForm */
    protected $form;

    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $entity_class;

    /** @var \Azura\Doctrine\Repository */
    protected $record_repo;

    /** @var string */
    protected $csrf_namespace;

    public function __construct(EntityForm $form)
    {
        $this->form = $form;

        $this->em = $form->getEntityManager();
        $this->entity_class = $form->getEntityClass();
        $this->record_repo = $form->getEntityRepository();
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|int|null $id
     * @return object|bool|null
     */
    protected function _doEdit(ServerRequestInterface $request, $id = null)
    {
        $record = $this->_getRecord($id);
        return $this->form->process($request, $record);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|int $id
     */
    protected function _doDelete(ServerRequestInterface $request, $id, $csrf_token): void
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $record = $this->_getRecord($id);

        if ($record instanceof $this->entity_class) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }

    /**
     * @param string|int|null $id
     * @return object|null
     */
    protected function _getRecord($id = null): ?object
    {
        if (null === $id) {
            return null;
        }

        $record = $this->record_repo->find($id);

        if (!$record instanceof $this->entity_class) {
            throw new \App\Exception\NotFound(__('%s not found.', $this->entity_class));
        }

        return $record;
    }
}
