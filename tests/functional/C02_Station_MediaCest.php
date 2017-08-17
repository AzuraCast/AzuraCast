<?php
class C01_Station_MediaCest extends CestAbstract
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

        $csrf = $this->di->get('csrf');
        $test_file = [
            'tmp_name'  => $test_song,
            'name'      => basename($test_song),
            'type'      => 'audio/mpeg',
            'size'      => filesize($test_song),
            'error'     => \UPLOAD_ERR_OK
        ];

        $I->sendPOST('/station/'.$station_id.'/files/upload', [
            'file' => '',
            'csrf' => $csrf->generate('files'),
            'flowIdentifier' => 'uploadtest',
            'flowChunkNumber' => 1,
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
            'media_name' => 'AzuraCast - AzuraCast Is Live!',
        ]);

        $I->amOnPage('/station/'.$station_id.'/files');

        $I->see('Media Manager');
    }
}
