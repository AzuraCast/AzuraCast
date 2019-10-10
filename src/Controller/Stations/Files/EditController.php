<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Exception\NotFoundException;
use App\Form\Form;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use Azura\Config;
use Azura\Exception;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Abstract out the Edit File functionality, as it has significant extra code.
 */
class EditController
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\SongRepository */
    protected $songRepo;

    /** @var Entity\Repository\StationMediaRepository */
    protected $mediaRepo;

    /** @var Entity\Repository\CustomFieldRepository */
    protected $customFieldRepo;

    /**
     * @param EntityManager $em
     * @param Entity\Repository\CustomFieldRepository $customFieldRepo
     * @param Entity\Repository\SongRepository $songRepo
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param Filesystem $filesystem
     * @param Config $config
     * @param Router $router
     */
    public function __construct(
        EntityManager $em,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        Entity\Repository\SongRepository $songRepo,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Filesystem $filesystem,
        Config $config,
        Router $router
    ) {
        $this->em = $em;
        $this->customFieldRepo = $customFieldRepo;
        $this->songRepo = $songRepo;
        $this->mediaRepo = $mediaRepo;
        $this->filesystem = $filesystem;
        $this->form_config = $config->get('forms/media', [
            'router' => $router,
        ]);
    }

    public function __invoke(ServerRequest $request, Response $response, $id): ResponseInterface
    {
        $station = $request->getStation();

        $fs = $this->filesystem->getForStation($station);

        $media = $this->mediaRepo->find($id, $station);

        if (!($media instanceof Entity\StationMedia)) {
            throw new NotFoundException(__('Media not found.'));
        }

        $form_config = $this->form_config;

        // Add custom fields to form configuration.
        $custom_fields = $this->customFieldRepo->fetchArray();

        foreach ($custom_fields as $custom_field) {
            $form_config['groups']['custom_fields']['elements'][$custom_field['id']] = [
                'text',
                [
                    'label' => $custom_field['name'],
                    'belongsTo' => 'custom_fields',
                ],
            ];
        }

        $form = new Form($form_config);

        // Populate custom fields in form.
        $media_array = $this->mediaRepo->toArray($media);
        if (!empty($custom_fields)) {
            $media_array['custom_fields'] = $this->customFieldRepo->getCustomFields($media);
        }

        $form->populate($media_array);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();
            unset($data['length']);

            // Detect rename.
            if ($data['path'] !== $media->getPath()) {
                $fs->rename($media->getPathUri(), $data['path']);
            }

            if (!empty($custom_fields)) {
                $this->customFieldRepo->setCustomFields($media, $data['custom_fields']);
                unset($data['custom_fields']);
            }

            $this->mediaRepo->fromArray($media, $data);

            // Handle uploaded artwork files.
            $files = $request->getUploadedFiles();
            if (!empty($files['art'])) {
                $file = $files['art'];

                /** @var UploadedFileInterface $file */
                if ($file->getError() === UPLOAD_ERR_OK) {
                    $this->mediaRepo->writeAlbumArt($media, $file->getStream()->getContents());
                } elseif ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception('Error ' . $file->getError() . ' in uploaded file!');
                }
            }

            if ($this->mediaRepo->writeToFile($media)) {
                $song_info = [
                    'title' => $media->getTitle(),
                    'artist' => $media->getArtist(),
                ];

                $song = $this->songRepo->getOrCreate($song_info);
                $song->update($song_info);
                $this->em->persist($song);

                $media->setSong($song);
            }

            $this->em->persist($media);
            $this->em->flush();

            $request->getFlash()->addMessage('<b>' . __('Media updated.') . '</b>', Flash::SUCCESS);

            $file_dir = (dirname($media->getPath()) === '.') ? '' : dirname($media->getPath());
            return $response->withRedirect($request->getRouter()->named('stations:files:index',
                    ['station_id' => $station->getId()]) . '#' . $file_dir, 302);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Edit Media'),
        ]);
    }
}
