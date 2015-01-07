<?php
use \Entity\Station;
use \Entity\StationManager;

class Stations_SubmitController extends \DF\Controller\Action
{
    public function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }

    public function indexAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->submit);

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $files = $form->processFiles('stations');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            \DF\Image::resizeImage($data['image_url'], $data['image_url'], 150, 150);

            $record = new Station;
            $record->fromArray($data);
            $record->is_active = false;
            $record->is_special = false;
            $record->save();

            $user = \DF\Auth::getLoggedInUser();

            // Make the current user an administrator of the new station.
            if (!$this->acl->isAllowed('administer all'))
            {
                $manager = new StationManager;
                $manager->email = $user->email;
                $manager->station = $record;
                $manager->save();
            }

            // Notify all existing managers.
            $station_managers_raw = $this->em->createQuery('SELECT sm FROM Entity\StationManager sm JOIN sm.station s WHERE s.is_active = 1')
                ->getArrayResult();
            $email_to = array();

            foreach($station_managers_raw as $manager)
            {
                $email_key = md5($manager['email']);
                $email_to[$email_key] = $manager['email'];
            }

            if ($email_to)
            {
                \DF\Messenger::send(array(
                    'to'        => $email_to,
                    'subject'   => 'New Station Submitted For Review',
                    'template'  => 'newstation',

                    'vars'      => array(
                        'form'      => $form->populate($data)->renderMessage(),
                    ),
                ));
            }

            $this->alert('Your station has been submitted. Thank you! We will contact you with any questions or additional information.', 'green');
            $this->redirectHere();
            return;
        }

        $this->view->setVar('title', 'New Station Submission');
        $this->renderForm($form);
    }
}