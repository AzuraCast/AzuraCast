<?php
namespace App\Controller\Admin\Stations;

use Azura\Cache;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class CloneController
{
    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /** @var Configuration */
    protected $configuration;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\StationRepository */
    protected $record_repo;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @param Configuration $configuration
     * @param array $form_config
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityManager $em, Cache $cache, Configuration $configuration, array $form_config)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->configuration = $configuration;
        $this->form_config = $form_config;

        $this->record_repo = $em->getRepository(Entity\Station::class);
    }

    public function __invoke(Request $request, Response $response, $id): ResponseInterface
    {
        $record = $this->record_repo->find((int)$id);

        if (!($record instanceof Entity\Station)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Station')));
        }

        $form = new \AzuraForms\Form($this->form_config);

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

            $unset_values = [
                'short_name',
                'radio_base_dir',
                'nowplaying',
                'nowplaying_timestamp',
                'is_streamer_live',
                'needs_restart',
                'has_started',
            ];

            foreach($unset_values as $unset_value) {
                unset($new_record_data[$unset_value]);
            }

            if ($data['clone_media'] === 'share') {
                $new_record_data['radio_media_dir'] = $record->getRadioMediaDir();
            } else {
                unset($new_record_data['radio_media_dir'], $new_record_data['storage_used']);
            }

            // Trigger normal creation process of station.
            $new_record = $this->record_repo->create($new_record_data);

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

            $request->getSession()->flash(__('Changes saved.'), 'green');

            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Clone Station: %s', $record->getName())
        ]);
    }
}
