<?php
namespace Controller\Admin;

use Entity;
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
            $station_repo = $this->em->getRepository(Record::class);

            if (!($record instanceof Record)) {
                $record = $station_repo->create($data, $this->di);
            } else {
                $oldAdapter = $record->frontend_type;
                $record->fromArray($this->em, $data);
                $this->em->persist($record);
                $this->em->flush();

                if ($oldAdapter !== $record->frontend_type) {
                    $station_repo->resetMounts($record, $this->di);
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
        $record = $this->em->getRepository(Record::class)->find($id);

        if (!($record instanceof Record)) {
            throw new \Exception('Source station not found!');
        }

        $form = new \App\Form($this->config->forms->station_clone);

        $form->setDefaults([
            'name' => $record->name.' - Copy',
            'description' => $record->description,
        ]);

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            // Assemble new station from old station based on form parameters.
            $new_record_data = $record->toArray($this->em);
            $new_record_data['name'] = $data['name'];
            $new_record_data['description'] = $data['description'];

            unset($new_record_data['radio_base_dir']);

            if ($data['clone_media'] === 'share') {
                $new_record_data['radio_media_dir'] = $record->getRadioMediaDir();
            } else {
                unset($new_record_data['radio_media_dir']);
            }

            // Trigger normal creation process of station.

            /** @var Entity\Repository\StationRepository $station_repo */
            $station_repo = $this->em->getRepository(Record::class);

            $new_record = $station_repo->create($new_record_data, $this->di);
            $new_record->writeConfiguration($this->di);

            // Copy associated records if applicable.
            if ($data['clone_media'] === 'copy') {
                copy($record->getRadioMediaDir(), $new_record->getRadioMediaDir());
            }

            $clone_types = [
                'playlists' => [
                    'clone' => ($data['clone_playlists'] == 1),
                    'relation' => 'playlists',
                    'entity' => Entity\StationPlaylist::class,
                ],
                'streamers' => [
                    'clone' => ($data['clone_streamers'] == 1),
                    'relation' => 'streamers',
                    'entity' => Entity\StationStreamer::class,
                ],
                'permissions' => [
                    'clone' => ($data['clone_permissions'] == 1),
                    'relation' => 'permissions',
                    'entity' => Entity\RolePermission::class,
                ],
            ];

            foreach($clone_types as $clone_type) {
                if ($clone_type['clone']) {
                    $dest_class = new \ReflectionClass($clone_type['entity']);

                    foreach($record->{$clone_type['relation']} as $source_record) {
                        $dest_record_data = $source_record->toArray($this->em);
                        unset($dest_record_data['id'], $dest_record_data['station_id']);

                        $dest_record = $dest_class->newInstance();
                        $dest_record->station = $new_record;
                        $dest_record->fromArray($this->em, $dest_record_data);
                        $this->em->persist($dest_record);
                    }

                    $this->em->flush();
                }
            }

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        return $this->renderForm($form, 'edit', sprintf(_('Clone Station: %s'), $record->name));
    }

    public function deleteAction()
    {
        $record = $this->em->getRepository(Record::class)->find($this->getParam('id'));

        if ($record) {
            $record->removeConfiguration($this->di);

            $this->em->remove($record);
            $this->em->flush();
        }

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null, 'csrf' => null]);
    }
}