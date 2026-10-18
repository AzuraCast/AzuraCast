<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Listener;
use App\Entity\ListenerDevice;
use App\Entity\ListenerLocation;
use App\Entity\Song;
use App\Entity\SongHistory;
use Carbon\CarbonImmutable;
use Codeception\Util\Shared\Asserts;
use DateInterval;
use DateTime;
use FunctionalTester;
use League\Csv\Reader;

class Api_Stations_ReportsCest extends CestAbstract
{
    use Asserts;

    /**
     * @before setupComplete
     * @before login
     */
    public function viewReports(FunctionalTester $I): void
    {
        $I->wantTo('View various station reports via API.');

        $station = $this->getTestStation();
        $uriBase = '/api/station/' . $station->id;

        $I->sendGet($uriBase . '/reports/overview/charts');
        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/best-and-worst');
        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/by-stream');
        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/by-browser');
        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/by-country');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function downloadListenerReportsCsv(FunctionalTester $I): void
    {
        $I->wantTo('Download station listener report CSV via API.');

        $this->createListenerWithFormulaUserAgent();

        $station = $this->getTestStation();
        $uriBase = '/api/station/' . $station->id;

        $startDateTime = (new DateTime())->sub(DateInterval::createFromDateString('30 days'));
        $endDateTime = new DateTime();

        $requestUrl = $uriBase . '/listeners?' . http_build_query(
            [
                    'format' => 'csv',
                    'start' => $startDateTime->format('Y-m-d\TH:i:s.v\Z'),
                    'end' => $endDateTime->format('Y-m-d\TH:i:s.v\Z'),
                ]
        );

        $csvHeaders = [
            'IP',
            'Start Time',
            'End Time',
            'Seconds Connected',
            'User Agent',
            'Mount Type',
            'Mount Name',
            'Device: Client',
            'Device: Is Mobile',
            'Device: Is Browser',
            'Device: Is Bot',
            'Device: Browser Family',
            'Device: OS Family',
            'Location: Description',
            'Location: Country',
            'Location: Region',
            'Location: City',
            'Location: Latitude',
            'Location: Longitude',
        ];

        $listenerRow = $this->testReportCsv($I, $requestUrl, $csvHeaders)->first();

        $this->assertSame("'=1+1", $listenerRow['User Agent']);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function downloadHistoryReportCsv(FunctionalTester $I): void
    {
        $I->wantTo('Download station timeline report CSV via API.');

        $this->createSongHistoryWithFormulaSong();

        $station = $this->getTestStation();
        $uriBase = '/api/station/' . $station->id;

        $startDateTime = (new DateTime())->sub(DateInterval::createFromDateString('30 days'));
        $endDateTime = new DateTime();

        $requestUrl = $uriBase . '/history?' . http_build_query(
            [
                    'format' => 'csv',
                    'start' => $startDateTime->format('Y-m-d\TH:i:s.v\Z'),
                    'end' => $endDateTime->format('Y-m-d\TH:i:s.v\Z'),
                ]
        );

        $csvHeaders = [
            'Date',
            'Time',
            'Listeners',
            'Delta',
            'Track',
            'Artist',
            'Playlist',
            'Streamer',
        ];

        $historyRow = $this->testReportCsv($I, $requestUrl, $csvHeaders)->first();

        $this->assertSame("'=1+1", $historyRow['Track']);
        $this->assertSame("'@SUM(A1)", $historyRow['Artist']);
    }

    protected function testReportCsv(
        FunctionalTester $I,
        string $url,
        array $headerFields
    ): Reader {
        $I->sendGet($url);

        $response = $I->grabResponse();

        $csvReader = Reader::fromString($response);
        $csvReader->setHeaderOffset(0);

        $csvHeaders = $csvReader->getHeader();

        $this->assertTrue(
            count($csvHeaders) > 0,
            'CSV is not empty'
        );

        foreach ($headerFields as $csvHeaderField) {
            $this->assertContains(
                $csvHeaderField,
                $csvHeaders,
                'CSV has header field'
            );
        }

        return $csvReader;
    }

    private function createListenerWithFormulaUserAgent(): void
    {
        $listener = new Listener(
            station: $this->getTestStation(),
            mount: null,
            remote: null,
            hls_stream: null,
            listener_uid: 1,
            listener_ip: '127.0.0.1',
            listener_user_agent: '=1+1',
            listener_hash: 'formula-user-agent',
            timestamp_start: CarbonImmutable::now()->subHour(),
            timestamp_end: null,
            location: ListenerLocation::fromArray(['description' => 'Unknown']),
            device: ListenerDevice::fromArray(['client' => 'Unknown']),
        );

        $this->em->persist($listener);
        $this->em->flush();
    }

    private function createSongHistoryWithFormulaSong(): void
    {
        $songHistory = new SongHistory(
            $this->getTestStation(),
            Song::createFromArray(['title' => '=1+1', 'artist' => '@SUM(A1)'])
        );
        $songHistory->listeners_start = 0;

        $this->em->persist($songHistory);
        $this->em->flush();
    }
}
