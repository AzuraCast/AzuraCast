<?php
namespace App\Controller\Stations;

use App\Entity\Station;
use App\Form\EntityForm;
use App\Http\RequestHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractStationCrudController
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
        $station = RequestHelper::getStation($request);
        $this->form->setStation($station);

        $record = $this->_getRecord($station, $id);
        $result = $this->form->process($request, $record);

        if (false !== $result) {
            $this->em->refresh($station);
        }

        return $result;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|int $id
     */
    protected function _doDelete(ServerRequestInterface $request, $id, $csrf_token): void
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $record = $this->_getRecord(RequestHelper::getStation($request), $id);

        if ($record instanceof $this->entity_class) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }

    /**
     * @param Station $station
     * @param string|int|null $id
     * @return object|null
     */
    protected function _getRecord(Station $station, $id = null): ?object
    {
        if (null === $id) {
            return null;
        }

        $record = $this->record_repo->findOneBy(['id' => $id, 'station_id' => $station->getId()]);

        if (!$record instanceof $this->entity_class) {
            throw new \App\Exception\NotFound(__('%s not found.', $this->entity_class));
        }

        return $record;
    }
}
