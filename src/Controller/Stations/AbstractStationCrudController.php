<?php

namespace App\Controller\Stations;

use App\Entity\Station;
use App\Exception;
use App\Exception\CsrfValidationException;
use App\Exception\NotFoundException;
use App\Form\EntityForm;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractStationCrudController
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
        $station = $request->getStation();
        $this->form->setStation($station);

        $record = $this->getRecord($station, $id);
        return $this->form->process($request, $record);
    }

    /**
     * @param Station $station
     * @param string|int|null $id
     */
    protected function getRecord(Station $station, $id = null): ?object
    {
        if (null === $id) {
            return null;
        }

        $record = $this->record_repo->findOneBy(['id' => $id, 'station' => $station]);

        if (!$record instanceof $this->entity_class) {
            throw new NotFoundException(__('Record not found.'));
        }

        return $record;
    }

    /**
     * @param ServerRequest $request
     * @param string|int $id
     * @param string $csrf
     *
     * @throws NotFoundException
     * @throws Exception
     * @throws CsrfValidationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function doDelete(ServerRequest $request, $id, $csrf): void
    {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $record = $this->getRecord($request->getStation(), $id);

        if ($record instanceof $this->entity_class) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }
}
