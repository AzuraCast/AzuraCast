<?php
namespace Modules\Frontend\Controllers;

use PVL\Utilities;

use Entity\Action;
use Entity\Station;
use Entity\StationStream;
use Entity\StationManager;
use Entity\Podcast;

class SubmitController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }

    public function stationAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->submit_station);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $stream = $data['stream'];
            unset($data['stream']);

            $files = $form->processFiles('stations');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            // Set up initial station record.
            $record = new Station;
            $record->fromArray($data);
            $record->is_active = false;
            $record->save();

            // Set up first stream, connected to station.
            $stream_record = new StationStream;
            $stream_record->fromArray($stream);
            $stream_record->name = 'Primary Stream';
            $stream_record->station = $record;
            $stream_record->is_default = 1;
            $stream_record->is_active = 0;
            $stream_record->save();

            // Make the current user an administrator of the new station.
            if (!$this->acl->isAllowed('administer all'))
            {
                $user = $this->auth->getLoggedInUser();

                $manager = new StationManager;
                $manager->email = $user->email;
                $manager->station = $record;
                $manager->save();
            }

            /*
             * Now notify only PR account.
             *
            // Notify all existing managers.
            $station_managers_raw = StationManager::getAllActiveManagers();
            $station_emails = Utilities::ipull($station_managers_raw, 'email');

            $network_administrators = Action::getUsersWithAction('administer all');
            $network_emails = Utilities::ipull($network_administrators, 'email');

            $email_to = array_merge($station_emails, $network_emails);
             */

            $email_to = array('pr@ponyvillelive.com');

            if ($email_to)
            {
                \DF\Messenger::send(array(
                    'to'        => $email_to,
                    'subject'   => 'New Station Submitted For Review',
                    'template'  => 'newstation',
                    'vars'      => array(
                        'form'      => $form->populate($_POST),
                    ),
                ));
            }

            $this->alert('Your station has been submitted. Thank you! We will contact you with any questions or additional information.', 'green');
            $this->redirectHome();
            return;
        }

        $this->renderForm($form, 'edit', 'Submit Your Station');
    }

    public function showAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->submit_show);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $files = $form->processFiles('podcasts');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            // Set up initial station record.
            $record = new Podcast;
            $record->fromArray($data);
            $record->is_approved = false;

            // Make the current user an administrator of the new station.
            if (!$this->acl->isAllowed('administer all'))
            {
                $user = $this->auth->getLoggedInUser();
                $record->contact_email = $user->email;
            }

            $record->save();

            // Notify all existing managers.
            $network_administrators = Action::getUsersWithAction('administer all');
            $email_to = Utilities::ipull($network_administrators, 'email');

            if ($email_to)
            {
                \DF\Messenger::send(array(
                    'to'        => $email_to,
                    'subject'   => 'New Podcast/Show Submitted For Review',
                    'template'  => 'newshow',
                    'vars'      => array(
                        'form'      => $form->populate($_POST),
                    ),
                ));
            }

            $this->alert('Your show has been submitted. Thank you! We will contact you with any questions or additional information.', 'green');
            $this->redirectHome();
            return;
        }

        $this->renderForm($form, 'edit', 'Submit Your Show');
    }
}