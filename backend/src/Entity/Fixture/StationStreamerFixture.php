<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Entity\StationStreamerBroadcast;
use App\Environment;
use App\Flysystem\StationFilesystems;
use App\Radio\Enums\StreamFormats;
use App\Utilities\Time;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationStreamerFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Environment $environment,
        private readonly StationFilesystems $filesystems
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $streamerUsername = getenv('INIT_STREAMER_USERNAME');
        $streamerPassword = getenv('INIT_STREAMER_PASSWORD');

        if (empty($streamerUsername) || empty($streamerPassword)) {
            return;
        }

        $station = $this->getReference('station', Station::class);

        $station->enable_streamers = true;

        $backendConfig = $station->backend_config;
        $backendConfig->record_streams = true;
        $backendConfig->record_streams_format = StreamFormats::default()->value;
        $station->backend_config = $backendConfig;

        $manager->persist($station);

        $streamer = new StationStreamer($station);
        $streamer->streamer_username = $streamerUsername;
        $streamer->streamer_password = $streamerPassword;
        $manager->persist($streamer);

        // Add a sample broadcast
        $startTime = Time::nowUtc()->subWeek();
        $endTime = $startTime->addMinutes(5);

        $recordingsFs = $this->filesystems->getRecordingsFilesystem($station);

        $originalPath = $this->environment->getBaseDirectory() . '/resources/error.mp3';

        $recordingPath = sprintf(
            '%s/stream_%s.mp3',
            $streamerUsername,
            $startTime->format('Ymd-his')
        );

        $recordingsFs->upload($originalPath, $recordingPath);

        $recording = new StationStreamerBroadcast(
            $streamer,
            $startTime
        );
        $recording->timestampEnd = $endTime;
        $recording->recordingPath = $recordingPath;

        $manager->persist($recording);
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StationFixture::class,
        ];
    }
}
