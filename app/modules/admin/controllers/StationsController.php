<?php
namespace Controller\Admin;

use Entity\Station as Record;

class StationsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer stations');
    }

    public function indexAction()
    {
        $this->view->stations = $this->em->createQuery('SELECT s FROM Entity\Station s ORDER BY s.name ASC')
            ->getArrayResult();
    }

    public function editAction()
    {
        $form = new \App\Form($this->config->forms->station);

        if ($this->hasParam('id')) {
            $id = (int)$this->getParam('id');
            $record = $this->em->getRepository(Record::class)->find($id);
            $form->setDefaults($record->toArray($this->em, false, true));
        }

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Record)) {
                $station_repo = $this->em->getRepository(Record::class);
                $station_repo->create($data, $this->di);
            } else {
                $record->fromArray($this->em, $data);

                $this->em->persist($record);
                $this->em->flush();
            }

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        $record = $this->em->getRepository(Record::class)->find($this->getParam('id'));
        if ($record) {
            $ba = $record->getBackendAdapter($this->di);
            $fa = $record->getFrontendAdapter($this->di);

            $ba->stop();
            $fa->stop();

            $this->em->remove($record);
            $this->em->flush();
        }

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null, 'csrf' => null]);
    }
}