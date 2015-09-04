<?php
namespace Modules\Frontend\Controllers;

use Entity\Song;
use Entity\SongSubmission;
use PVL\Utilities;
use \GetId3\GetId3Core as GetId3;

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

    public function songAction()
    {
        $auth = $this->di->get('auth');
        $user = $auth->getLoggedInUser();

        $defaults = array(
            'artist'        => $user->name,
        );

        $form = new \DF\Form($this->current_module_config->forms->submit_song);
        $form->setDefaults($defaults);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            // Check if existing submission exists.
            $song = Song::getOrCreate($data);

            $existing_submission = SongSubmission::getRepository()->findOneBy(array('hash' => $song->id));
            if ($existing_submission instanceof SongSubmission)
                throw new \DF\Exception\DisplayOnly('This song has already been submitted through this system!');

            // Process song upload.
            $files = $form->processFiles('song_uploads');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            // Check song status.
            $song_file = $data['song_url'];
            if (empty($song_file))
                throw new \DF\Exception\DisplayOnly('No file uploaded! Please go back and select a file to submit.');

            $song_file_path = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.$song_file;

            // Verify MP3 extension.
            $song_file_extension = \DF\File::getFileExtension($song_file);
            if (strtolower($song_file_extension) !== 'mp3')
            {
                @unlink($song_file_path);
                throw new \DF\Exception\DisplayOnly('The file you uploaded was not an MP3 file! Please go back and resubmit your file.');
            }

            // Analyze and clean up ID3 metadata.
            $getId3 = new GetId3();
            $getId3->encoding = 'UTF-8';

            $audio = $getId3->analyze($song_file_path);

            if (isset($audio['error']))
            {
                @unlink($song_file_path);
                throw new \DF\Exception\DisplayOnly(sprintf('Error at reading audio properties with GetId3: %s.', $audio['error']));
            }

            $metadata = array(
                'File Format'       => strtoupper($audio['fileformat']),
                'Play Time'         => $audio['playtime_string'],
                'Bitrate'           => round($audio['audio']['bitrate'] / 1024).'kbps',
                'Bitrate Mode'      => strtoupper($audio['audio']['bitrate_mode']),
                'Channels'          => $audio['audio']['channels'],
                'Sample Rate'       => $audio['audio']['sample_rate'],
            );

            $data['song_metadata'] = $metadata;

            // TODO: Write the Artist / Title specified back into the MP3 file directly.

            // Set up initial station record.
            $record = new SongSubmission;
            $record->song = $song;

            $auth = $this->di->get('auth');
            $record->user = $auth->getLoggedInUser();

            $record->fromArray($data);
            $record->save();

            // Notify all existing managers.
            $network_administrators = Action::getUsersWithAction('administer all');
            $email_to = Utilities::ipull($network_administrators, 'email');

            // Pull list of station managers for the specified stations.
            $station_managers = array();

            $short_names = Station::getShortNameLookup();
            foreach($data['stations'] as $station_key)
            {
                if (isset($short_names[$station_key]))
                {
                    $station_id = $short_names[$station_key]['id'];
                    $station = Station::find($station_id);

                    foreach($station->managers as $manager)
                    {
                        $station_managers[] = $manager->email;
                    }
                }
            }

            $email_to = array_merge($email_to, $station_managers);

            // Trigger e-mail notice.
            define('DF_FORCE_EMAIL', true);

            if ($email_to)
            {
                \DF\Url::forceSchemePrefix(true);
                $download_url = \PVL\Service\AmazonS3::url($data['song_url']);

                \DF\Messenger::send(array(
                    'to'        => $email_to,
                    'subject'   => 'New Song Submitted to Station',
                    'template'  => 'newsong',
                    'vars'      => array(
                        'download_url'  => $download_url,
                        'metadata'      => $metadata,
                        'form'          => $form->populate($_POST),
                    ),
                ));
            }

            $this->alert('Your song has been submitted. Thank you!', 'green');
            $this->redirectHome();
            return;
        }

        $this->renderForm($form, 'edit', 'Submit a Song');
    }
}