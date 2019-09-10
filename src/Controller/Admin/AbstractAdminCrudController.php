<?php
namespace App\Controller\Admin;

use App\Exception\NotFound;
use App\Form\EntityForm;
use App\Http\ServerRequest;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;

abstract class AbstractAdminCrudController
{
    /** @var EntityForm */
    protected $form;

    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $entity_class;

    /** @var Repository */
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
     * @param ServerRequest $request
     * @param string|int|null $id
     * @return object|bool|null
     */
    protected function _doEdit(ServerRequest $request, $id = null)
    {
        $record = $this->_getRecord($id);
        return $this->form->process($request, $record);
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
            throw new NotFound(__('Record not found.'));
        }

        return $record;
    }

    /**
     * @param ServerRequest $request
     * @param $id
     * @param $csrf
     */
    protected function _doDelete(ServerRequest $request, $id, $csrf): void
    {
        $request->getSession()->getCsrf()->verify($csrf, $this->csrf_namespace);

        $record = $this->_getRecord($id);

        if ($record instanceof $this->entity_class) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }
}
