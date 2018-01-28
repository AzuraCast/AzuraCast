<?php
namespace Controller\Admin;

use Entity;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class StationsController extends BaseController
{
    /** @var Entity\Repository\StationRepository */
    protected $record_repo;

    public function __construct(Container $di)
    {
        parent::__construct($di);

        $this->record_repo = $this->em->getRepository(Entity\Station::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $this->view->stations = $this->em->createQuery('SELECT s FROM Entity\Station s ORDER BY s.name ASC')
            ->getArrayResult();

        return $this->render($response, 'admin/stations/index');
    }

    public function editAction(Request $request, Response $response, $args): Response
    {
        $form = new \App\Form($this->config->forms->station);

        if (!empty($args['id'])) {
            $id = (int)$args['id'];
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

                $record->writeConfiguration($this->di);

                if ($oldAdapter !== $record->getFrontendType()) {
                    $this->record_repo->resetMounts($record, $this->di);
                }
            }

            // Clear station cache.
            /** @var \App\Cache $cache */
            $cache = $this->di[\App\Cache::class];

            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectToName($response, 'admin:stations:index');
        }

        $this->view->form = $form;
        return $this->render($response, 'admin/stations/edit');
    }

    public function cloneAction(Request $request, Response $response, $args): Response
    {
        $id = (int)$args['id'];
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

            /** @var \App\Cache $cache */
            $cache = $this->di[\App\Cache::class];

            $cache->remove('stations');

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectToName($response, 'admin:stations:index');
        }

        return $this->renderForm($response, $form, 'edit', sprintf(_('Clone Station: %s'), $record->getName()));
    }

    public function deleteAction(Request $request, Response $response, $args): Response
    {
        $record = $this->record_repo->find($args['id']);

        if ($record instanceof Entity\Station) {
            $record->removeConfiguration($this->di);

            $this->em->remove($record);
            $this->em->flush();
        }

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectToName($response, 'admin:stations:index');
    }
}