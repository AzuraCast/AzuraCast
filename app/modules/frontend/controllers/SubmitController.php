<?php
namespace Modules\Frontend\Controllers;


use PVL\Utilities;
use \GetId3\GetId3Core as GetId3;

use Entity\Action;
use Entity\Station;
use Entity\StationStream;
use Entity\Podcast;
use Entity\Song;
use Entity\SongSubmission;

class SubmitController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }

    /**
     * Submit a new station.
     *
     * @throws \DF\Exception
     */
    public function stationAction()
    {
        $this->em->getFilters()->disable('softdelete');

        $user = $this->auth->getLoggedInUser();

        // Check for existing submissions.
        $existing_submissions = $this->em->createQuery('SELECT s, u FROM Entity\Station s JOIN s.managers u
            WHERE (s.deleted_at IS NULL OR s.deleted_at IS NOT NULL)
            AND u.id = :user_id')
            ->setParameter('user_id', $user->id)
            ->getArrayResult();

        if ($existing_submissions)
        {
            $message = '<b>You have already submitted the following stations to the system:</b>';
            $message .= '<ul>';

            foreach($existing_submissions as $station)
            {
                if ($station['deleted_at'])
                    $status = 'Declined';
                elseif ($station['is_active'])
                    $status = 'Approved';
                else
                    $status = 'Pending Review';

                $message .= '<li><b>'.$station['name'].':</b> '.$status.'</li>';
            }

            $message .= '</ul>';
            $message .= 'Please contact the PVL team for questions related to these stations, and do not resubmit them!';

            $this->flash($message, 'info');
        }

        // Initialize the form.
        $form = new \DF\Form($this->current_module_config->forms->submit_station);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $stream = $data['stream'];
            unset($data['stream']);

            $files = $form->processFiles('stations');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            // Check for existing station by name.
            $existing_station = Station::getRepository()->findOneBy(array('name' => $data['name']));
            if ($existing_station instanceof Station)
                throw new \DF\Exception('A station with this name already exists! Please do not submit duplicate stations.');

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

    /**
     * Submit a new show/podcast.
     *
     * @throws \DF\Exception
     */
    public function showAction()
    {
        $this->em->getFilters()->disable('softdelete');

        $user = $this->auth->getLoggedInUser();

        // Check for existing submissions.
        $existing_submissions = $this->em->createQuery('SELECT p, u FROM Entity\Podcast p JOIN p.managers u
            WHERE (p.deleted_at IS NULL OR p.deleted_at IS NOT NULL)
            AND u.id = :user_id')
            ->setParameter('user_id', $user->id)
            ->getArrayResult();

        if ($existing_submissions)
        {
            $message = '<b>You have already submitted the following shows to the system:</b>';
            $message .= '<ul>';

            foreach($existing_submissions as $podcast)
            {
                if ($podcast['deleted_at'])
                    $status = 'Declined';
                elseif ($podcast['is_approved'])
                    $status = 'Approved';
                else
                    $status = 'Pending Review';

                $message .= '<li><b>'.$podcast['name'].':</b> '.$status.'</li>';
            }

            $message .= '</ul>';
            $message .= 'Please contact the PVL team for questions related to these shows, and do not resubmit them!';

            $this->flash($message, 'info');
        }

        // Initialize the form.
        $form = new \DF\Form($this->current_module_config->forms->submit_show);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $files = $form->processFiles('podcasts');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            // Check for existing podcast by name.
            $existing_podcast = Podcast::getRepository()->findOneBy(array('name' => $data['name']));
            if ($existing_podcast instanceof Podcast)
                throw new \DF\Exception('A podcast with this name already exists! Please do not submit duplicate stations.');

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

    /*
     * Submit a new song.
     */
    public function songAction()
    {
        // Generate temporary token for this session.
        $this->view->token = $this->_generateSongHash();

        // Produce list of stations.
        $stations = array();
        $all_stations = Station::fetchArray();

        foreach($all_stations as $station)
        {
            if ($station['category'] == 'audio')
            {
                $stations[$station['short_name']] = '<b>'.$station['name'].'</b> - '.$station['genre'];
            }
        }

        $this->view->stations = $stations;
    }

    public function songconfirmAction()
    {
        // Handle files submitted directly to page.
        $ignore_files = (int)$this->getParam('ignore_files');
        $request = $this->di->get('request');
        if ($request->hasFiles() && !$ignore_files)
        {
            $this->_processSongUpload();
        }

        // Validate song identifier token.
        $token = $this->_getSongHashToken();
        if (!$this->_validateSongHash($token))
            return $this->redirectFromHere(array('action' => 'song'));

        // Check that any stations were selected
        if (!$this->hasParam('stations'))
            throw new \DF\Exception\DisplayOnly('You did not specify any stations!');

        // Check for uploaded songs.
        $temp_dir_name = 'song_uploads';
        $temp_dir = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.$temp_dir_name;

        $all_files = glob($temp_dir.DIRECTORY_SEPARATOR.$token.'*.mp3');

        if (empty($all_files))
            throw new \DF\Exception\DisplayOnly('No files were uploaded!');

        $songs = array();
        $getId3 = new GetId3();
        $getId3->encoding = 'UTF-8';

        foreach($all_files as $song_file_base)
        {
            $song_file_path = $temp_dir.DIRECTORY_SEPARATOR.basename($song_file_base);

            // Attempt to analyze the MP3.
            $audio = $getId3->analyze($song_file_path);

            if (isset($audio['error']))
            {
                @unlink($song_file_path);
                throw new \DF\Exception\DisplayOnly(sprintf('Error at reading audio properties with GetId3: %s.', $audio['error'][0]));
            }

            if (isset($audio['tags']['id3v1']['title']))
            {
                $song_data = array(
                    'title' => $audio['tags']['id3v1']['title'][0],
                    'artist' => $audio['tags']['id3v1']['artist'][0],
                );
            }
            elseif (isset($audio['tags']['id3v2']['title']))
            {
                $song_data = array(
                    'title' => $audio['tags']['id3v2']['title'][0],
                    'artist' => $audio['tags']['id3v2']['artist'][0],
                );
            }
            else
            {
                @unlink($song_file_path);
                continue;
            }

            // Check if existing submission exists.
            $song = Song::getOrCreate($song_data);

            $existing_submission = SongSubmission::getRepository()->findOneBy(array('hash' => $song->id));
            if ($existing_submission instanceof SongSubmission)
            {
                @unlink($song_file_path);
                continue;
            }

            // Create record in database.
            $metadata = array(
                'File Format'       => strtoupper($audio['fileformat']),
                'Play Time'         => $audio['playtime_string'],
                'Bitrate'           => round($audio['audio']['bitrate'] / 1024).'kbps',
                'Bitrate Mode'      => strtoupper($audio['audio']['bitrate_mode']),
                'Channels'          => $audio['audio']['channels'],
                'Sample Rate'       => $audio['audio']['sample_rate'],
            );

            $record = new SongSubmission;
            $record->song = $song;

            $auth = $this->di->get('auth');
            $record->user = $auth->getLoggedInUser();

            $record->title = $song_data['title'];
            $record->artist = $song_data['artist'];
            $record->song_metadata = $metadata;
            $record->stations = $this->getParam('stations');

            $song_download_url = $record->uploadSong($song_file_path);

            $record->save();

            // Append information to e-mail to stations.
            $song_row = array(
                'Download URL'      => '<a href="'.$song_download_url.'" target="_blank">'.$song_download_url.'</a>',
                'Title'             => $song_data['title'],
                'Artist'            => $song_data['artist'],
            ) + $metadata;

            $songs[] = $song_row;
        }

        if (!empty($songs))
        {
            // Notify all existing managers.
            $network_administrators = Action::getUsersWithAction('administer all');
            $email_to = Utilities::ipull($network_administrators, 'email');

            // Pull list of station managers for the specified stations.
            $station_managers = array();

            $short_names = Station::getShortNameLookup();
            foreach ($this->getParam('stations') as $station_key)
            {
                if (isset($short_names[$station_key]))
                {
                    $station_id = $short_names[$station_key]['id'];
                    $station = Station::find($station_id);

                    foreach ($station->managers as $manager)
                    {
                        $station_managers[] = $manager->email;
                    }
                }
            }

            $email_to = array_merge($email_to, $station_managers);

            // Trigger e-mail notice.
            if (!empty($email_to))
            {
                \DF\Messenger::send(array(
                    'to' => $email_to,
                    'subject' => 'New Song(s) Submitted to Station',
                    'template' => 'newsong',
                    'vars' => array(
                        'songs' => $songs,
                    ),
                ));
            }
        }

        // Have to manually call view because a view was already rendered (e-mail was sent).
        // TODO: Fix this. It's dumb.
        $this->view->songs = $songs;
        return $this->view->render('submit', 'songconfirm');
    }

    public function songuploadAction()
    {
        $upload_result = $this->_processSongUpload();

        if ($upload_result)
        {
            $this->doNotRender();
            return $this->response->setContent('1');
        }
    }

    protected function _processSongUpload()
    {
        // Validate token.
        $token = $this->_getSongHashToken();
        if (!$this->_validateSongHash($token))
            die('Could not validate unique ID token.');

        // Validate that any files are uploaded.
        $request = $this->di->get('request');
        if (!$request->hasFiles())
            die('No files uploaded!');

        // Check for upload directory.
        $base_dir = DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'song_uploads';

        if (!file_exists($base_dir))
            @mkdir($base_dir);

        // Loop through all uploaded files.
        $all_uploaded_files = $request->getUploadedFiles();

        foreach($all_uploaded_files as $file)
        {
            if (!$file->isUploadedFile())
                continue;

            $file_ext = strtolower($file->getExtension());
            if ($file_ext !== 'mp3')
                die('File uploaded is not an MP3!');

            $new_file_path = $base_dir.DIRECTORY_SEPARATOR.$token.'_'.mt_rand(100, 999).'.'.$file->getExtension();
            $file->moveTo($new_file_path);
        }

        // Return a success code.
        return true;
    }

    protected function _getSongHashToken()
    {
        $token = $this->getParam('token');

        if (empty($token) || strlen($token) < 15)
            return false;

        // Clean up token string.
        $token = preg_replace("/[^A-Za-z0-9_]/", '', $token);

        return $token;
    }

    protected function _validateSongHash($hash)
    {
        $old_hash_prefix = substr($hash, 0, 10);

        $new_hash = $this->_generateSongHash();
        $new_hash_prefix = substr($new_hash, 0, 10);

        return (strcmp($old_hash_prefix, $new_hash_prefix) === 0);
    }

    protected function _generateSongHash()
    {
        $auth = $this->di->get('auth');
        $user = $auth->getLoggedInUser();

        $upload_hash_prefix = substr(md5($user->id.$user->email), 0, 10);
        $upload_hash = $upload_hash_prefix.'_'.time().'_'.mt_rand(10000, 99999);
        return $upload_hash;
    }
}