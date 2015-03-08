<?php
namespace Modules\Frontend\Controllers;

use DF\Utilities;

use Entity\Station;
use Entity\StationStream;
use Entity\StationManager;

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

            // Notify all existing managers.
            $station_managers_raw = StationManager::getAllActiveManagers();
            $email_to = Utilities::ipull($station_managers_raw, 'email');

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
}