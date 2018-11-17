<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Abstract out the Edit File functionality, as it has significant extra code.
 * @package Controller\Stations\Files
 */
class EditController extends FilesControllerAbstract
{
    public function editAction(Request $request, Response $response, $station_id, $media_id): ResponseInterface
    {
        $station = $request->getStation();

        $media = $this->media_repo->findOneBy([
            'station_id' => $station_id,
            'id' => $media_id
        ]);

        if (!($media instanceof Entity\StationMedia)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Media')));
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

        $form = new \AzuraForms\Form($form_config);

        // Populate custom fields in form.
        $media_array = $this->media_repo->toArray($media);
        if (!empty($custom_fields)) {
            $media_array['custom_fields'] = $this->media_repo->getCustomFields($media);
        }

        $form->populate($media_array);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();
            unset($data['length']);

            // Detect rename.
            if ($data['path'] !== $media->getPath()) {
                list($data['path'], $path_full) = $this->_filterPath($station->getRadioMediaDir(), $data['path']);
                rename($media->getFullPath(), $path_full);
            }

            if (!empty($custom_fields)) {
                $this->media_repo->setCustomFields($media, $data['custom_fields']);
                unset($data['custom_fields']);
            }

            $this->media_repo->fromArray($media, $data);

            // Handle uploaded artwork files.
            $files = $request->getUploadedFiles();
            if (!empty($files['art'])) {
                $file = $files['art'];

                /** @var UploadedFileInterface $file */
                if ($file->getError() === UPLOAD_ERR_OK) {
                    $art_resource = imagecreatefromstring($file->getStream()->getContents());
                    $media->setArt($art_resource);
                } else if ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                    throw new \Azura\Exception('Error ' . $file->getError() . ' in uploaded file!');
                }
            }

            if ($media->writeToFile()) {
                /** @var Entity\Repository\SongRepository $song_repo */
                $song_repo = $this->em->getRepository(Entity\Song::class);

                $media->setSong($song_repo->getOrCreate([
                    'title' => $media->getTitle(),
                    'artist' => $media->getArtist(),
                ]));
            }

            $this->em->persist($media);
            $this->em->flush();

            $request->getSession()->flash('<b>' . __('%s updated.', __('Media')) . '</b>', 'green');

            $file_dir = (dirname($media->getPath()) === '.') ? '' : dirname($media->getPath());
            return $response->withRedirect($request->getRouter()->named('stations:files:index', ['station' => $station_id]).'#'.$file_dir, 302);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' =>__('Edit %s', __('Media'))
        ]);
    }
}
