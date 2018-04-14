<?php
namespace Controller\Admin;

use App\Cache;
use App\Csrf;
use App\Flash;
use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Configuration;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class StationsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Cache */
    protected $cache;

    /** @var Adapters */
    protected $adapters;

    /** @var Configuration */
    protected $configuration;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'admin_stations';

    /** @var array */
    protected $edit_form_config;

    /** @var array */
    protected $clone_form_config;

    /** @var Entity\Repository\StationRepository */
    protected $record_repo;

    public function __construct(EntityManager $em, Flash $flash, Cache $cache, Adapters $adapters, Configuration $configuration, Csrf $csrf, array $edit_form_config, array $clone_form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->cache = $cache;
        $this->adapters = $adapters;
        $this->configuration = $configuration;
        $this->csrf = $csrf;

        $this->edit_form_config = $edit_form_config;
        $this->clone_form_config = $clone_form_config;

        $this->record_repo = $this->em->getRepository(Entity\Station::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $stations = $this->em->createQuery('SELECT s FROM Entity\Station s ORDER BY s.name ASC')
            ->getArrayResult();

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'admin/stations/index', [
            'stations' => $stations,
            'csrf' => $this->csrf->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        $form = new \AzuraForms\Form($this->edit_form_config);

        if (!empty($id)) {
            $record = $this->record_repo->find((int)$id);
            $form->populate($this->record_repo->toArray($record, false, true));
        } else {
            $record = null;
        }

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\Station)) {
                $record = $this->record_repo->create($data, $this->adapters, $this->configuration);
            } else {
                $oldAdapter = $record->getFrontendType();
                $this->record_repo->fromArray($record, $data);
                $this->em->persist($record);
                $this->em->flush();

                $this->configuration->writeConfiguration($record);

                if ($oldAdapter !== $record->getFrontendType()) {
                    $this->record_repo->resetMounts($record, $this->adapters->getFrontendAdapter($record));
                }
            }

            // Clear station cache.
            $this->cache->remove('stations');

            $this->flash->alert(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Station')), 'green');

            return $response->redirectToRoute('admin:stations:index');
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'admin/stations/edit', [
            'form' => $form,
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Station')),
        ]);
    }

    public function cloneAction(Request $request, Response $response, $id): Response
    {
        $record = $this->record_repo->find((int)$id);

        if (!($record instanceof Entity\Station)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Station')));
        }

        $form = new \AzuraForms\Form($this->clone_form_config);

        $form->populate([
            'name' => $record->getName().' - Copy',
            'description' => $record->getDescription(),
        ]);

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            // Assemble new station from old station based on form parameters.
            $new_record_data = $this->record_repo->toArray($record);
            $new_record_data['name'] = $data['name'];
            $new_record_data['description'] = $data['description'];

            unset($new_record_data['short_name'], $new_record_data['radio_base_dir']);

            if ($data['clone_media'] === 'share') {
                $new_record_data['radio_media_dir'] = $record->getRadioMediaDir();
            } else {
                unset($new_record_data['radio_media_dir']);
            }

            // Trigger normal creation process of station.
            $new_record = $this->record_repo->create($new_record_data, $this->adapters, $this->configuration);

            // Force port reassignment
            $this->configuration->assignRadioPorts($new_record, true);

            $this->configuration->writeConfiguration($new_record);

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
            $this->cache->remove('stations');

            $this->flash->alert(__('Changes saved.'), 'green');

            return $response->redirectToRoute('admin:stations:index');
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Clone Station: %s', $record->getName())
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        $record = $this->record_repo->find((int)$id);

        if ($record instanceof Entity\Station) {
            $this->record_repo->destroy($record, $this->adapters, $this->configuration);
        }

        $this->flash->alert(__('%s deleted.', __('Station')), 'green');

        return $response->redirectToRoute('admin:stations:index');
    }
}