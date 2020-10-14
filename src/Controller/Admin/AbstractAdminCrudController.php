<?php

namespace App\Controller\Admin;

use App\Exception\NotFoundException;
use App\Form\EntityForm;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractAdminCrudController
{
    protected EntityForm $form;

    protected EntityManagerInterface $em;

    protected string $entity_class;

    protected ObjectRepository $record_repo;

    protected string $csrf_namespace;

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
     *
     * @return object|bool|null
     */
    protected function doEdit(ServerRequest $request, $id = null)
    {
        $record = $this->getRecord($id);
        return $this->form->process($request, $record);
    }

    /**
     * @param string|int|null $id
     */
    protected function getRecord($id = null): ?object
    {
        if (null === $id) {
            return null;
        }

        $record = $this->record_repo->find($id);

        if (!$record instanceof $this->entity_class) {
            throw new NotFoundException(__('Record not found.'));
        }

        return $record;
    }

    /**
     * @param ServerRequest $request
     * @param string|int $id
     * @param string $csrf
     */
    protected function doDelete(ServerRequest $request, $id, $csrf): void
    {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $record = $this->getRecord($id);

        if ($record instanceof $this->entity_class) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }
}
