<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Form\Form;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Abstract out the Edit File functionality, as it has significant extra code.
 */
class EditController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param Filesystem $filesystem
     * @param Config $config
     * @param Router $router
     */
    public function __construct(
        EntityManager $em,
        Filesystem $filesystem,
        Config $config,
        Router $router
    ) {
        $this->em = $em;
        $this->filesystem = $filesystem;
        $this->form_config = $config->get('forms/media', [
            'router' => $router,
        ]);
    }

    public function __invoke(ServerRequest $request, Response $response, $station_id, $media_id): ResponseInterface
    {
        $station = $request->getStation();

        $fs = $this->filesystem->getForStation($station);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        $media = $media_repo->findOneBy([
            'station_id' => $station_id,
            'id' => $media_id
        ]);

        if (!($media instanceof Entity\StationMedia)) {
            throw new \App\Exception\NotFound(__('Media not found.'));
        }

        $form_config = $this->form_config;

        // Add custom fields to form configuration.

        /** @var \Azura\Doctrine\Repository $custom_fields_repo */
        $custom_fields_repo = $this->em->getRepository(Entity\CustomField::class);
        $custom_fields = $custom_fields_repo->fetchArray();

        foreach($custom_fields as $custom_field) {
            $form_config['groups']['custom_fields']['elements'][$custom_field['id']] = [
                'text', [
                    'label' => $custom_field['name'],
                    'belongsTo' => 'custom_fields',
                ],
            ];
        }

        $form = new Form($form_config);

        // Populate custom fields in form.
        $media_array = $media_repo->toArray($media);
        if (!empty($custom_fields)) {
            $media_array['custom_fields'] = $media_repo->getCustomFields($media);
        }

        $form->populate($media_array);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();
            unset($data['length']);

            // Detect rename.
            if ($data['path'] !== $media->getPath()) {
                $path_full = 'media://'.$data['path'];
                $fs->rename($media->getPathUri(), $path_full);
            }

            if (!empty($custom_fields)) {
                $media_repo->setCustomFields($media, $data['custom_fields']);
                unset($data['custom_fields']);
            }

            $media_repo->fromArray($media, $data);

            // Handle uploaded artwork files.
            $files = $request->getUploadedFiles();
            if (!empty($files['art'])) {
                $file = $files['art'];

                /** @var UploadedFileInterface $file */
                if ($file->getError() === UPLOAD_ERR_OK) {
                    $media_repo->writeAlbumArt($media, $file->getStream()->getContents());
                } else if ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                    throw new \Azura\Exception('Error ' . $file->getError() . ' in uploaded file!');
                }
            }

            if ($media_repo->writeToFile($media)) {
                /** @var Entity\Repository\SongRepository $song_repo */
                $song_repo = $this->em->getRepository(Entity\Song::class);

                $song_info = [
                    'title' => $media->getTitle(),
                    'artist' => $media->getArtist(),
                ];

                $song = $song_repo->getOrCreate($song_info);
                $song->update($song_info);
                $this->em->persist($song);

                $media->setSong($song);
            }

            $this->em->persist($media);
            $this->em->flush();

            $request->getSession()->flash('<b>' . __('Media updated.') . '</b>', 'green');

            $file_dir = (dirname($media->getPath()) === '.') ? '' : dirname($media->getPath());
            return $response->withRedirect($request->getRouter()->named('stations:files:index', ['station' => $station_id]).'#'.$file_dir, 302);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' =>__('Edit Media')
        ]);
    }
}
