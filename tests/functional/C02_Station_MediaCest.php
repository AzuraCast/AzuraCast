<?php
class C02_Station_MediaCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMedia(FunctionalTester $I)
    {
        $I->wantTo('Upload a song to a station.');

        $station_id = $this->test_station->getId();

        $test_song_orig = APP_INCLUDE_ROOT.'/resources/error.mp3';
        $test_song = tempnam(sys_get_temp_dir(), 'azuracast');
        copy($test_song_orig, $test_song);

        /** @var \Azura\Session $session */
        $session = $this->di->get(\Azura\Session::class);
        $csrf = $session->getCsrf();

        $test_file = new \Slim\Psr7\UploadedFile(
            $test_song,
            basename($test_song),
            'audio/mpeg',
            filesize($test_song)
        );

        $I->sendPOST('/station/'.$station_id.'/files/upload', [
            'file' => '',
            'csrf' => $csrf->generate('stations_files'),
            'flowIdentifier' => 'uploadtest',
            'flowChunkNumber' => 1,
            'flowCurrentChunkSize' => filesize($test_song),
            'flowFilename' => 'error.mp3',
            'flowTotalSize' => filesize($test_song),
            'flowTotalChunks' => 1,
        ], [
            'file_data' => $test_file
        ]);

        $I->seeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET('/station/'.$station_id.'/files/list');

        $I->seeResponseContainsJson([
            'media_name' => 'AzuraCast.com - AzuraCast is Live!',
        ]);

        $I->amOnPage('/station/'.$station_id.'/files');

        $I->see('Music Files');
    }
}
