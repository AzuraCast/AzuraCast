<?php
use \Entity\ArchiveSong;

class Mlpma_SongController extends \PVL\Controller\Action\Mlpma
{
    public function indexAction()
    {
        $this->redirectFromHere(array('action' => 'view'));
    }

    public function viewAction()
    {
        $id = (int)$this->_getParam('id');
        $song = ArchiveSong::find($id);

        if (!($song instanceof ArchiveSong))
            throw new \DF\Exception\DisplayOnly('Song not found!');
    }

    public function editAction()
    {
        $this->acl->checkPermission('administer mlpma songs');

        $id = (int)$this->_getParam('id');
        $song = ArchiveSong::find($id);

        if (!($song instanceof ArchiveSong))
            throw new \DF\Exception\DisplayOnly('Song not found!');

        $form = new \DF\Form($this->current_module_config->forms->song);

        $record = $song->toArray();
        unset($record['art_path']);
        $form->setDefaults($record);

        if ($_POST)
        {
            if ($form->isValid($_POST))
            {
                $data = $form->getValues();

                $files = $form->processFiles('mlpma_temp');
                foreach($files as $file_field => $file_paths)
                {
                    if (!empty($file_paths[1]))
                    {
                        $image_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$file_paths[1];
                        $thumb_path = $image_path.'.thumb.jpg';

                        @file_put_contents($thumb_path, '');

                        \DF\Image::resizeImage($image_path, $thumb_path, 100, 100);
                        @unlink($image_path);

                        $data[$file_field] = $thumb_path;
                    }
                }

                $song->fromArray($data);

                $song->fixPaths();
                $song->writeToFile();

                $song->save();

                $this->alert('<b>Song updated!</b>', 'green');

                $default_url = \DF\Url::route(array('module' => 'mlpma'));
                $this->redirectToStoredReferrer('mlpma_song', $default_url);
                return;
            }
        }
        else
        {
            $this->storeReferrer('mlpma_song');
        }

        $this->view->headTitle('Edit Song');
        $this->renderForm($form);
    }

    public function deleteAction()
    {
        $record = ArchiveSong::find($this->_getParam('id'));
        if ($record)
            $record->delete();
            
        $this->alert('Record deleted.', 'green');
        $this->redirectToReferrer();
    }
}