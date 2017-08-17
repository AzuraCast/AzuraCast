<?php
namespace Controller\Admin;

use Entity;

class StationsController extends BaseController
{
    /** @var Entity\Repository\StationRepository */
    protected $record_repo;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->record_repo = $this->em->getRepository(Entity\Station::class);
    }

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
            $record = $this->record_repo->find($id);
            $form->setDefaults($this->record_repo->toArray($record, false, true));
        } else {
            $record = null;
        }

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\Station)) {
                $record = $this->record_repo->create($data, $this->di);
            } else {
                $oldAdapter = $record->getFrontendType();
                $this->record_repo->fromArray($record, $data);
                $this->em->persist($record);
                $this->em->flush();

                if ($oldAdapter !== $record->getFrontendType()) {
                    $this->record_repo->resetMounts($record, $this->di);
                }
            }

            $record->writeConfiguration($this->di);

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        $this->view->form = $form;
    }

    public function cloneAction()
    {
        $id = (int)$this->getParam('id');
        $record = $this->record_repo->find($id);

        if (!($record instanceof Entity\Station)) {
            throw new \Exception('Source station not found!');
        }

        $form = new \App\Form($this->config->forms->station_clone);

        $form->setDefaults([
            'name' => $record->getName().' - Copy',
            'description' => $record->getDescription(),
        ]);

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            // Assemble new station from old station based on form parameters.
            $new_record_data = $this->record_repo->toArray($record);
            $new_record_data['name'] = $data['name'];
            $new_record_data['description'] = $data['description'];

            unset($new_record_data['radio_base_dir']);

            if ($data['clone_media'] === 'share') {
                $new_record_data['radio_media_dir'] = $record->getRadioMediaDir();
            } else {
                unset($new_record_data['radio_media_dir']);
            }

            // Trigger normal creation process of station.
            $new_record = $this->record_repo->create($new_record_data, $this->di);
            $new_record->writeConfiguration($this->di);

            // Copy associated records if applicable.
            if ($data['clone_media'] === 'copy') {
                copy($record->getRadioMediaDir(), $new_record->getRadioMediaDir());
            }

            if ($data['clone_playlists'] == 1) {
                foreach ($record->getPlaylists() as $source_record) {
                    $dest_record_data = $this->record_repo->toArray($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = new Entity\StationPlaylist($new_record);
                    $this->record_repo->fromArray($dest_record, $dest_record_data);
                    $this->em->persist($dest_record);
                }
            }

            if ($data['clone_streamers'] == 1) {
                foreach ($record->getStreamers() as $source_record) {
                    $dest_record_data = $this->record_repo->toArray($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = new Entity\StationStreamer($new_record);
                    $this->record_repo->fromArray($dest_record, $dest_record_data);
                    $this->em->persist($dest_record);
                }
            }

            if ($data['clone_permissions'] == 1) {
                foreach ($record->getPermissions() as $source_record) {
                    $dest_record_data = $this->record_repo->toArray($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = new Entity\RolePermission($source_record->getRole(), $new_record);
                    $this->record_repo->fromArray($dest_record, $dest_record_data);
                    $this->em->persist($dest_record);
                }
            }

            $this->em->flush();

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        return $this->renderForm($form, 'edit', sprintf(_('Clone Station: %s'), $record->getName()));
    }

    public function deleteAction()
    {
        $record = $this->record_repo->find($this->getParam('id'));

        if ($record instanceof Entity\Station) {
            $record->removeConfiguration($this->di);

            $this->em->remove($record);
            $this->em->flush();
        }

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null, 'csrf' => null]);
    }
}