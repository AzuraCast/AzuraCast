<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Enums\StationBackendPerformanceModes;
use App\Entity\Interfaces\StationMountInterface;
use App\Entity\Station;
use App\Entity\StationBackendConfiguration;
use App\Entity\StationMount;
use App\Entity\StationPlaylist;
use App\Entity\StationRemote;
use App\Entity\StationSchedule;
use App\Entity\StationStreamerBroadcast;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\AudioProcessingMethods;
use App\Radio\Enums\CrossfadeModes;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\LiquidsoapQueues;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;
use App\Radio\FallbackFile;
use App\Radio\StereoTool;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigWriter implements EventSubscriberInterface
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Liquidsoap $liquidsoap,
        private readonly FallbackFile $fallbackFile
    ) {
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WriteLiquidsoapConfiguration::class => [
                ['writeHeaderFunctions', 35],
                ['writePlaylistConfiguration', 30],
                ['writeCrossfadeConfiguration', 25],
                ['writeHarborConfiguration', 20],
                ['writePreBroadcastConfiguration', 10],
                ['writeLocalBroadcastConfiguration', 5],
                ['writeHlsBroadcastConfiguration', 2],
                ['writeRemoteBroadcastConfiguration', 0],
                ['writePostBroadcastConfiguration', -5],
            ],
        ];
    }

    public function writeCustomConfigurationSection(WriteLiquidsoapConfiguration $event, string $sectionName): void
    {
        if ($event->isForEditing()) {
            $divider = self::getDividerString();
            $event->appendLines(
                [
                    $divider . $sectionName . $divider,
                ]
            );
            return;
        }

        $settings = $this->readSettings();
        if (!$settings->getEnableAdvancedFeatures()) {
            return;
        }

        $settings = $event->getStation()->getBackendConfig();
        $customConfig = $settings->getCustomConfigurationSection($sectionName);

        if (!empty($customConfig)) {
            $event->appendLines(
                [
                    '# Custom Configuration (Specified in Station Profile)',
                    $customConfig,
                ]
            );
        }
    }

    public function writePostProcessingSection(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();
        $settings = $station->getBackendConfig();

        switch ($settings->getAudioProcessingMethodEnum()) {
            case AudioProcessingMethods::Liquidsoap:
                // NRJ normalization
                $event->appendBlock(
                    <<<LIQ
                    # Normalization and Compression
                    radio = normalize(target = 0., window = 0.03, gain_min = -16., gain_max = 0., radio)
                    radio = compress.exponential(radio, mu = 1.0)
                    LIQ
                );
                break;

            case AudioProcessingMethods::MasterMe:
                // MasterMe Presets

                $lines = [
                    'radio = ladspa.master_me(',
                ];

                $preset = $settings->getMasterMePresetEnum();
                $presetOptions = $preset->getOptions();

                if (0 !== ($loudnessTarget = $settings->getMasterMeLoudnessTarget())) {
                    $presetOptions['target'] = $loudnessTarget;
                }

                foreach ($presetOptions as $presetKey => $presetVal) {
                    $presetVal = match (true) {
                        is_int($presetVal) => self::toFloat($presetVal, 0),
                        is_float($presetVal) => self::toFloat($presetVal),
                        is_bool($presetVal) => ($presetVal) ? 'true' : 'false',
                        default => $presetVal
                    };

                    $lines[] = '    ' . $presetKey . ' = ' . $presetVal . ',';
                }

                $lines[] = '    radio';
                $lines[] = ')';

                $event->appendLines($lines);
                break;

            case AudioProcessingMethods::StereoTool:
                // Stereo Tool processing
                if (!StereoTool::isReady($station)) {
                    return;
                }

                $stereoToolLibraryPath = StereoTool::getLibraryPath();
                $stereoToolBinary = $stereoToolLibraryPath . '/stereo_tool';

                $stereoToolConfiguration = $station->getRadioConfigDir()
                    . DIRECTORY_SEPARATOR . $settings->getStereoToolConfigurationPath();

                $stereoToolLicenseKey = $settings->getStereoToolLicenseKey();

                if (is_file($stereoToolBinary)) {
                    $stereoToolProcess = $stereoToolBinary . ' --silent - - -s ' . $stereoToolConfiguration;

                    if (!empty($stereoToolLicenseKey)) {
                        $stereoToolProcess .= ' -k "' . $stereoToolLicenseKey . '"';
                    }

                    $event->appendBlock(
                        <<<LIQ
                        # Stereo Tool Pipe
                        radio = pipe(replay_delay=1.0, process='{$stereoToolProcess}', radio)
                        LIQ
                    );
                } else {
                    $serverArch = php_uname('m');
                    $stereoToolLibrary = match ($serverArch) {
                        'x86' => $stereoToolLibraryPath . '/libStereoTool_intel32.so',
                        'aarch64', 'arm64' => $stereoToolLibraryPath . '/libStereoTool_arm64.so',
                        default => $stereoToolLibraryPath . '/libStereoTool_intel64.so',
                    };

                    if (!file_exists($stereoToolLibrary)) {
                        // Stereo Tool 10.0 uploaded using a different format.
                        $is64Bit = in_array($serverArch, ['x86_64', 'arm64'], true);
                        if ($is64Bit && file_exists($stereoToolLibraryPath . '/libStereoTool_64.so')) {
                            $stereoToolLibrary = $stereoToolLibraryPath . '/libStereoTool_64.so';
                        } elseif (file_exists(($stereoToolLibraryPath . '/libStereoTool.so'))) {
                            $stereoToolLibrary = $stereoToolLibraryPath . '/libStereoTool.so';
                        } else {
                            break;
                        }
                    }

                    $event->appendBlock(
                        <<<LIQ
                        # Stereo Tool Pipe
                        radio = stereotool(
                            library_file="{$stereoToolLibrary}",
                            license_key="{$stereoToolLicenseKey}",
                            preset="{$stereoToolConfiguration}",
                            radio
                        )
                        LIQ
                    );
                }
                break;

            case AudioProcessingMethods::None:
                // Noop
                break;
        }
    }

    public static function getDividerString(): string
    {
        return chr(7);
    }

    public function writeHeaderFunctions(WriteLiquidsoapConfiguration $event): void
    {
        if (!$event->isForEditing()) {
            $event->prependLines(
                [
                    '# WARNING! This file is automatically generated by AzuraCast.',
                    '# Do not update it directly!',
                ]
            );
        }

        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_TOP);

        $station = $event->getStation();

        $configDir = $station->getRadioConfigDir();
        $pidfile = $configDir . DIRECTORY_SEPARATOR . 'liquidsoap.pid';

        $socketFile = $configDir . DIRECTORY_SEPARATOR . 'liquidsoap.sock';

        $stationTz = self::cleanUpString($station->getTimezone());

        $event->appendBlock(
            <<<LIQ
            init.daemon.set(false)
            init.daemon.pidfile.path.set("{$pidfile}")

            log.stdout.set(true)
            log.file.set(false)

            settings.server.log.level.set(4)

            settings.server.socket.set(true)
            settings.server.socket.permissions.set(0o660)
            settings.server.socket.path.set("{$socketFile}")

            settings.harbor.bind_addrs.set(["0.0.0.0"])
            settings.encoder.metadata.export.set(["artist","title","album","song"])

            environment.set("TZ", "{$stationTz}")

            autodj_is_loading = ref(true)
            ignore(autodj_is_loading)

            autodj_ping_attempts = ref(0)
            ignore(autodj_ping_attempts)

            # Track live-enabled status.
            live_enabled = ref(false)
            ignore(live_enabled)

            # Track live transition for crossfades.
            to_live = ref(false)
            ignore(to_live)

            # Reimplement LS's now-deprecated drop_metadata function.
            def drop_metadata(~id=null(), s)
                let {metadata=_, ...tracks} = source.tracks(s)
                source(id=id, tracks)
            end

            # Transport for HTTPS outputs.
            https_transport = http.transport.ssl()
            ignore(https_transport)
            LIQ
        );

        $stationApiAuth = self::cleanUpString($station->getAdapterApiKey());
        $stationApiUrl = self::cleanUpString(
            (string)$this->environment->getInternalUri()
                ->withPath('/api/internal/' . $station->getId() . '/liquidsoap')
        );

        $event->appendBlock(
            <<<LIQ
            azuracast_api_url = "{$stationApiUrl}"
            azuracast_api_key = "{$stationApiAuth}"

            def azuracast_api_call(~timeout=2.0, url, payload) =
                full_url = "#{azuracast_api_url}/#{url}"

                log("API #{url} - Sending POST request to '#{full_url}' with body: #{payload}")
                try
                    response = http.post(full_url,
                        headers=[
                            ("Content-Type", "application/json"),
                            ("User-Agent", "Liquidsoap AzuraCast"),
                            ("X-Liquidsoap-Api-Key", "#{azuracast_api_key}")
                        ],
                        timeout=timeout,
                        data=payload
                    )

                    log("API #{url} - Response (#{response.status_code}): #{response}")
                    "#{response}"
                catch err do
                    log("API #{url} - Error: #{error.kind(err)} - #{error.message(err)}")
                    "false"
                end
            end
            LIQ
        );

        $mediaStorageLocation = $station->getMediaStorageLocation();

        if ($mediaStorageLocation->isLocal()) {
            $stationMediaDir = $mediaStorageLocation->getFilteredPath();

            $event->appendBlock(
                <<<LIQ
                station_media_dir = "{$stationMediaDir}"
                def azuracast_media_protocol(~rlog=_,~maxtime=_,arg) =
                    ["#{station_media_dir}/#{arg}"]
                end

                protocol.add(
                    "media",
                    azuracast_media_protocol,
                    doc="Pull files from AzuraCast media directory.",
                    syntax="media:uri"
                )
                LIQ
            );
        } else {
            $event->appendBlock(
                <<<LIQ
                def azuracast_media_protocol(~rlog=_,~maxtime,arg) =
                    timeout = 1000.0 * (maxtime - time())

                    j = json()
                    j.add("uri", arg)

                    [azuracast_api_call(timeout=timeout, "cp", json.stringify(j))]
                end

                protocol.add(
                    "media",
                    azuracast_media_protocol,
                    temporary=true,
                    doc="Pull files from AzuraCast media directory.",
                    syntax="media:uri"
                )
                LIQ
            );
        }

        $backendConfig = $station->getBackendConfig();

        $perfMode = $backendConfig->getPerformanceModeEnum();
        if ($perfMode !== StationBackendPerformanceModes::Disabled) {
            $gcSpaceOverhead = match ($backendConfig->getPerformanceModeEnum()) {
                StationBackendPerformanceModes::LessMemory => 20,
                StationBackendPerformanceModes::LessCpu => 140,
                StationBackendPerformanceModes::Balanced => 80,
                StationBackendPerformanceModes::Disabled => 0,
            };

            $event->appendBlock(
                <<<LIQ
                # Optimize Performance
                runtime.gc.set(runtime.gc.get().{
                  space_overhead = {$gcSpaceOverhead},
                  allocation_policy = 2
                })
                LIQ
            );
        }
    }

    public function writePlaylistConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();

        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_PRE_PLAYLISTS);

        // Set up playlists using older format as a fallback.
        $playlistVarNames = [];
        $genPlaylistWeights = [];
        $genPlaylistVars = [];

        $specialPlaylists = [
            'once_per_x_songs' => [
                '# Once per x Songs Playlists',
            ],
            'once_per_x_minutes' => [
                '# Once per x Minutes Playlists',
            ],
        ];

        $scheduleSwitches = [];
        $scheduleSwitchesInterrupting = [];
        $scheduleSwitchesRemoteUrl = [];

        $fallbackRemoteUrl = null;

        foreach ($station->getPlaylists() as $playlist) {
            if (!$playlist->getIsEnabled()) {
                continue;
            }

            $playlistVarName = self::getPlaylistVariableName($playlist);

            if (in_array($playlistVarName, $playlistVarNames, true)) {
                $playlistVarName .= '_' . $playlist->getId();
            }

            $scheduleItems = $playlist->getScheduleItems();

            $playlistVarNames[] = $playlistVarName;
            $playlistConfigLines = [];

            if (PlaylistSources::Songs === $playlist->getSource()) {
                $playlistFilePath = PlaylistFileWriter::getPlaylistFilePath($playlist);

                $playlistParams = [
                    'id="' . self::cleanUpString($playlistVarName) . '"',
                    'mime_type="audio/x-mpegurl"',
                ];

                $playlistMode = match ($playlist->getOrder()) {
                    PlaylistOrders::Sequential => 'normal',
                    PlaylistOrders::Shuffle => 'randomize',
                    PlaylistOrders::Random => 'random'
                };
                $playlistParams[] = 'mode="' . $playlistMode . '"';
                $playlistParams[] = 'reload_mode="watch"';
                $playlistParams[] = '"' . $playlistFilePath . '"';

                $playlistConfigLines[] = $playlistVarName . ' = playlist('
                    . implode(',', $playlistParams) . ')';

                if ($playlist->backendMerge()) {
                    $playlistConfigLines[] = $playlistVarName . ' = merge_tracks(id="merge_'
                        . self::cleanUpString($playlistVarName) . '", ' . $playlistVarName . ')';
                }

                $playlistConfigLines[] = $playlistVarName . ' = cue_cut(id="cue_'
                    . self::cleanUpString($playlistVarName) . '", ' . $playlistVarName . ')';
            } elseif (PlaylistRemoteTypes::Playlist === $playlist->getRemoteType()) {
                $playlistFunc = 'playlist("'
                    . self::cleanUpString($playlist->getRemoteUrl())
                    . '")';
                $playlistConfigLines[] = $playlistVarName . ' = ' . $playlistFunc;
            } else {
                // Special handling for Remote Stream URLs.
                $remoteUrl = $playlist->getRemoteUrl();
                if (null === $remoteUrl) {
                    continue;
                }

                $buffer = $playlist->getRemoteBuffer();
                $buffer = ($buffer < 1) ? StationPlaylist::DEFAULT_REMOTE_BUFFER : $buffer;

                $inputFunc = match ($playlist->getRemoteType()) {
                    PlaylistRemoteTypes::Stream => 'input.http',
                    default => 'input.external.ffmpeg'
                };

                $remoteUrlFunc = 'mksafe(buffer(buffer=' . $buffer . '., '
                    . $inputFunc . '("' . self::cleanUpString($remoteUrl) . '")))';

                if (0 === $scheduleItems->count()) {
                    $fallbackRemoteUrl = $remoteUrlFunc;
                    continue;
                }

                $playlistConfigLines[] = $playlistVarName . ' = ' . $remoteUrlFunc;
                $event->appendLines($playlistConfigLines);

                foreach ($scheduleItems as $scheduleItem) {
                    $playTime = $this->getScheduledPlaylistPlayTime($event, $scheduleItem);

                    $scheduleTiming = '({ ' . $playTime . ' }, ' . $playlistVarName . ')';
                    $scheduleSwitchesRemoteUrl[] = $scheduleTiming;
                }
                continue;
            }

            if ($playlist->getIsJingle()) {
                $playlistConfigLines[] = $playlistVarName . ' = drop_metadata(' . $playlistVarName . ')';
            }

            if (PlaylistTypes::Advanced === $playlist->getType()) {
                $playlistConfigLines[] = 'ignore(' . $playlistVarName . ')';
            }

            $event->appendLines($playlistConfigLines);

            switch ($playlist->getType()) {
                case PlaylistTypes::Standard:
                    if ($scheduleItems->count() > 0) {
                        foreach ($scheduleItems as $scheduleItem) {
                            $playTime = $this->getScheduledPlaylistPlayTime($event, $scheduleItem);

                            $scheduleTiming = $playlist->backendPlaySingleTrack()
                                ? '(predicate.at_most(1, {' . $playTime . '}), ' . $playlistVarName . ')'
                                : '({ ' . $playTime . ' }, ' . $playlistVarName . ')';

                            if ($playlist->backendInterruptOtherSongs()) {
                                $scheduleSwitchesInterrupting[] = $scheduleTiming;
                            } else {
                                $scheduleSwitches[] = $scheduleTiming;
                            }
                        }
                    } else {
                        $genPlaylistWeights[] = $playlist->getWeight();
                        $genPlaylistVars[] = $playlistVarName;
                    }
                    break;

                case PlaylistTypes::OncePerXSongs:
                case PlaylistTypes::OncePerXMinutes:
                    if (PlaylistTypes::OncePerXSongs === $playlist->getType()) {
                        $playlistScheduleVar = 'rotate(weights=[1,'
                            . $playlist->getPlayPerSongs() . '], [' . $playlistVarName . ', radio])';
                    } else {
                        $delaySeconds = $playlist->getPlayPerMinutes() * 60;
                        $delayTrackSensitive = $playlist->backendInterruptOtherSongs() ? 'false' : 'true';

                        $playlistScheduleVar = 'fallback(track_sensitive=' . $delayTrackSensitive . ', [delay(' . $delaySeconds . '., ' . $playlistVarName . '), radio])';
                    }

                    if ($scheduleItems->count() > 0) {
                        foreach ($scheduleItems as $scheduleItem) {
                            $playTime = $this->getScheduledPlaylistPlayTime($event, $scheduleItem);

                            $scheduleTiming = $playlist->backendPlaySingleTrack()
                                ? '(predicate.at_most(1, {' . $playTime . '}), ' . $playlistScheduleVar . ')'
                                : '({ ' . $playTime . ' }, ' . $playlistScheduleVar . ')';

                            if ($playlist->backendInterruptOtherSongs()) {
                                $scheduleSwitchesInterrupting[] = $scheduleTiming;
                            } else {
                                $scheduleSwitches[] = $scheduleTiming;
                            }
                        }
                    } else {
                        $specialPlaylists[$playlist->getType()->value][] = 'radio = ' . $playlistScheduleVar;
                    }
                    break;

                case PlaylistTypes::OncePerHour:
                    $minutePlayTime = $playlist->getPlayPerHourMinute() . 'm';

                    if ($scheduleItems->count() > 0) {
                        foreach ($scheduleItems as $scheduleItem) {
                            $playTime = '(' . $minutePlayTime . ') and ('
                                . $this->getScheduledPlaylistPlayTime($event, $scheduleItem) . ')';

                            $scheduleTiming = $playlist->backendPlaySingleTrack()
                                ? '(predicate.at_most(1, {' . $playTime . '}), ' . $playlistVarName . ')'
                                : '({ ' . $playTime . ' }, ' . $playlistVarName . ')';

                            if ($playlist->backendInterruptOtherSongs()) {
                                $scheduleSwitchesInterrupting[] = $scheduleTiming;
                            } else {
                                $scheduleSwitches[] = $scheduleTiming;
                            }
                        }
                    } else {
                        $scheduleTiming = $playlist->backendPlaySingleTrack()
                            ? '(predicate.at_most(1, {' . $minutePlayTime . '}), ' . $playlistVarName . ')'
                            : '({ ' . $minutePlayTime . ' }, ' . $playlistVarName . ')';

                        if ($playlist->backendInterruptOtherSongs()) {
                            $scheduleSwitchesInterrupting[] = $scheduleTiming;
                        } else {
                            $scheduleSwitches[] = $scheduleTiming;
                        }
                    }
                    break;

                case PlaylistTypes::Advanced:
                    // NOOP
            }
        }

        // Build "default" type playlists.
        $event->appendLines(
            [
                '# Standard Playlists',
                sprintf(
                    'radio = random(id="standard_playlists", weights=[%s], [%s])',
                    implode(', ', $genPlaylistWeights),
                    implode(', ', $genPlaylistVars)
                ),
            ]
        );

        if (!empty($scheduleSwitches)) {
            $event->appendLines(['# Standard Schedule Switches']);

            // Chunk scheduled switches to avoid hitting the max amount of playlists in a switch()
            foreach (array_chunk($scheduleSwitches, 168, true) as $scheduleSwitchesChunk) {
                $scheduleSwitchesChunk[] = '({true}, radio)';

                $event->appendLines(
                    [
                        sprintf(
                            'radio = switch(id="schedule_switch", track_sensitive=true, [ %s ])',
                            implode(', ', $scheduleSwitchesChunk)
                        ),
                    ]
                );
            }
        }

        // Add in special playlists if necessary.
        foreach ($specialPlaylists as $playlistConfigLines) {
            if (count($playlistConfigLines) > 1) {
                $event->appendLines($playlistConfigLines);
            }
        }

        if (!empty($scheduleSwitchesInterrupting)) {
            $event->appendLines(['# Interrupting Schedule Switches']);

            foreach (array_chunk($scheduleSwitchesInterrupting, 168, true) as $scheduleSwitchesChunk) {
                $scheduleSwitchesChunk[] = '({true}, radio)';

                $event->appendLines(
                    [
                        sprintf(
                            'radio = switch(id="schedule_switch", track_sensitive=false, [ %s ])',
                            implode(', ', $scheduleSwitchesChunk)
                        ),
                    ]
                );
            }
        }

        if (!$station->useManualAutoDJ()) {
            $event->appendBlock(
                <<< LIQ
                # AutoDJ Next Song Script
                def autodj_next_song() =
                    response = azuracast_api_call(
                        "nextsong",
                        ""
                    )
                    if (response == "") or (response == "false") then
                        null()
                    else
                        r = request.create(response)
                        if request.resolve(r) then
                            r
                        else
                            null()
                       end
                    end
                end

                # Delayed ping for AutoDJ Next Song
                def wait_for_next_song(autodj)
                    autodj_ping_attempts.set(autodj_ping_attempts() + 1)

                    if source.is_ready(autodj) then
                        log("AutoDJ is ready!")
                        autodj_is_loading.set(false)
                        -1.0
                    elsif autodj_ping_attempts() > 200 then
                        log("AutoDJ could not be initialized within the specified timeout.")
                        autodj_is_loading.set(false)
                        -1.0
                    else
                        0.5
                    end
                end

                dynamic = request.dynamic(id="next_song", timeout=20.0, retry_delay=10., autodj_next_song)
                dynamic = cue_cut(id="cue_next_song", dynamic)

                dynamic_startup = fallback(
                    id = "dynamic_startup",
                    track_sensitive = false,
                    [
                        dynamic,
                        source.available(
                            blank(id = "autodj_startup_blank", duration = 120.),
                            predicate.activates({autodj_is_loading()})
                        )
                    ]
                )
                radio = fallback(id="autodj_fallback", track_sensitive = true, [dynamic_startup, radio])

                ref_dynamic = ref(dynamic);
                thread.run.recurrent(delay=0.25, { wait_for_next_song(ref_dynamic()) })
                LIQ
            );
        }

        // Handle remote URL fallbacks.
        if (null !== $fallbackRemoteUrl) {
            $event->appendBlock(
                <<< LIQ
                remote_url = {$fallbackRemoteUrl}
                radio = fallback(id="fallback_remote_url", track_sensitive = false, [remote_url, radio])
                LIQ
            );
        }

        $requestsQueueName = LiquidsoapQueues::Requests->value;
        $interruptingQueueName = LiquidsoapQueues::Interrupting->value;

        $event->appendBlock(
            <<< LIQ
            requests = request.queue(id="{$requestsQueueName}")
            requests = cue_cut(id="cue_{$requestsQueueName}", requests)
            radio = fallback(id="requests_fallback", track_sensitive = true, [requests, radio])

            interrupting_queue = request.queue(id="{$interruptingQueueName}")
            interrupting_queue = cue_cut(id="cue_{$interruptingQueueName}", interrupting_queue)
            radio = fallback(id="interrupting_fallback", track_sensitive = false, [interrupting_queue, radio])
            LIQ
        );

        if (!empty($scheduleSwitchesRemoteUrl)) {
            $event->appendLines(['# Remote URL Schedule Switches']);

            foreach (array_chunk($scheduleSwitchesRemoteUrl, 168, true) as $scheduleSwitchesChunk) {
                $scheduleSwitchesChunk[] = '({true}, radio)';
                $event->appendLines(
                    [
                        sprintf(
                            'radio = switch(id="schedule_switch", track_sensitive=false, [ %s ])',
                            implode(', ', $scheduleSwitchesChunk)
                        ),
                    ]
                );
            }
        }

        $event->appendBlock(
            <<<LIQ
            # Skip command (used by web UI)
            def add_skip_command(s) =
                def skip(_) =
                    source.skip(s)
                    "Done!"
                end

                server.register(namespace="radio", usage="skip", description="Skip the current song.", "skip",skip)
            end

            add_skip_command(radio)
            
            # Apply amplification metadata (if supplied)
            radio = amplify(override="liq_amplify", 1., radio)
            LIQ
        );

        // Replaygain metadata
        $settings = $station->getBackendConfig();

        if ($settings->useReplayGain()) {
            $event->appendBlock(
                <<<LIQ
                # Replaygain Metadata
                enable_replaygain_metadata()
                radio = replaygain(radio)
                LIQ
            );
        }
    }

    /**
     * Given a scheduled playlist, return the time criteria that Liquidsoap can use to determine when to play it.
     *
     * @param WriteLiquidsoapConfiguration $event
     * @param StationSchedule $playlistSchedule
     * @return string
     */
    private function getScheduledPlaylistPlayTime(
        WriteLiquidsoapConfiguration $event,
        StationSchedule $playlistSchedule
    ): string {
        $startTime = $playlistSchedule->getStartTime();
        $endTime = $playlistSchedule->getEndTime();

        // Handle multi-day playlists.
        if ($startTime > $endTime) {
            $playTimes = [
                self::formatTimeCode($startTime) . '-23h59m59s',
                '00h00m-' . self::formatTimeCode($endTime),
            ];

            $playlistScheduleDays = $playlistSchedule->getDays();
            if (!empty($playlistScheduleDays) && count($playlistScheduleDays) < 7) {
                $currentPlayDays = [];
                $nextPlayDays = [];

                foreach ($playlistScheduleDays as $day) {
                    $currentPlayDays[] = (($day === 7) ? '0' : $day) . 'w';

                    $day++;
                    if ($day > 7) {
                        $day = 1;
                    }
                    $nextPlayDays[] = (($day === 7) ? '0' : $day) . 'w';
                }

                $playTimes[0] = '(' . implode(' or ', $currentPlayDays) . ') and ' . $playTimes[0];
                $playTimes[1] = '(' . implode(' or ', $nextPlayDays) . ') and ' . $playTimes[1];
            }

            return '(' . implode(') or (', $playTimes) . ')';
        }

        // Handle once-per-day playlists.
        $playTime = ($startTime === $endTime)
            ? self::formatTimeCode($startTime)
            : self::formatTimeCode($startTime) . '-' . self::formatTimeCode($endTime);

        $playlistScheduleDays = $playlistSchedule->getDays();
        if (!empty($playlistScheduleDays) && count($playlistScheduleDays) < 7) {
            $playDays = [];

            foreach ($playlistScheduleDays as $day) {
                $playDays[] = (($day === 7) ? '0' : $day) . 'w';
            }
            $playTime = '(' . implode(' or ', $playDays) . ') and ' . $playTime;
        }

        // Handle start-date and end-date boundaries.
        $startDate = $playlistSchedule->getStartDate();
        $endDate = $playlistSchedule->getEndDate();

        if (!empty($startDate) || !empty($endDate)) {
            $tzObject = $event->getStation()->getTimezoneObject();

            $customFunctionBody = [];

            $scheduleMethod = 'schedule_' . $playlistSchedule->getIdRequired() . '_date_range';
            $customFunctionBody[] = 'def ' . $scheduleMethod . '() =';

            $conditions = [];

            if (!empty($startDate)) {
                $startDateObj = CarbonImmutable::createFromFormat('Y-m-d', $startDate, $tzObject);

                if (false !== $startDateObj) {
                    $startDateObj = $startDateObj->setTime(0, 0);

                    $customFunctionBody[] = '    # ' . $startDateObj->__toString();
                    $customFunctionBody[] = '    range_start = ' . $startDateObj->getTimestamp() . '.';
                    $conditions[] = 'range_start <= current_time';
                }
            }

            if (!empty($endDate)) {
                $endDateObj = CarbonImmutable::createFromFormat('Y-m-d', $endDate, $tzObject);

                if (false !== $endDateObj) {
                    $endDateObj = $endDateObj->setTime(23, 59, 59);

                    $customFunctionBody[] = '    # ' . $endDateObj->__toString();
                    $customFunctionBody[] = '    range_end = ' . $endDateObj->getTimestamp() . '.';

                    $conditions[] = 'current_time <= range_end';
                }
            }

            $customFunctionBody[] = '    current_time = time()';
            $customFunctionBody[] = '    result = (' . implode(' and ', $conditions) . ')';
            $customFunctionBody[] = '    result';
            $customFunctionBody[] = 'end';
            $event->appendLines($customFunctionBody);

            $playTime = $scheduleMethod . '() and ' . $playTime;
        }

        return $playTime;
    }

    public function writeCrossfadeConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $settings = $event->getStation()->getBackendConfig();

        // Write pre-crossfade section.
        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_PRE_FADE);

        // Crossfading happens before the live broadcast is mixed in, because of buffer issues.
        $crossfadeType = $settings->getCrossfadeTypeEnum();

        if ($settings->isCrossfadeEnabled()) {
            $crossfade = self::toFloat($settings->getCrossfade());
            $crossDuration = self::toFloat($settings->getCrossfadeDuration());

            if (CrossfadeModes::Smart === $crossfadeType) {
                $crossfadeFunc = 'cross.smart(old, new, fade_in=' . $crossfade
                    . ', fade_out=' . $crossfade . ')';
            } else {
                $crossfadeFunc = 'cross.simple(old.source, new.source, fade_in=' . $crossfade
                    . ', fade_out=' . $crossfade . ')';
            }

            $event->appendBlock(
                <<<LS
                def live_aware_crossfade(old, new) =
                    if to_live() then
                        # If going to the live show, play a simple sequence
                        sequence([fade.out(old.source),fade.in(new.source)])
                    else
                        # Otherwise, use the smart transition
                        {$crossfadeFunc}
                    end
                end

                radio = cross(minimum=0., duration={$crossDuration}, live_aware_crossfade, radio)
                LS
            );
        }

        if ($settings->isAudioProcessingEnabled() && !$settings->getPostProcessingIncludeLive()) {
            $this->writePostProcessingSection($event);
        }
    }

    public function writeHarborConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();

        if (!$station->getEnableStreamers()) {
            return;
        }

        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_PRE_LIVE);

        $settings = $station->getBackendConfig();
        $charset = $settings->getCharset();
        $djMount = $settings->getDjMountPoint();
        $recordLiveStreams = $settings->recordStreams();

        $event->appendBlock(
            <<< LIQ
            # DJ Authentication
            last_authenticated_dj = ref("")
            live_dj = ref("")

            def dj_auth(login) =
                auth_info =
                    if (login.user == "source" or login.user == "") and (string.match(pattern="(:|,)+", login.password)) then
                        auth_string = string.split(separator="(:|,)", login.password)
                        {user = list.nth(default="", auth_string, 0),
                        password = list.nth(default="", auth_string, 2)}
                    else
                        {user = login.user, password = login.password}
                    end

                response = azuracast_api_call(
                    timeout=5.0,
                    "auth",
                    json.stringify(auth_info)
                )

                if (response == "true") then
                    last_authenticated_dj.set(auth_info.user)
                    true
                else
                    false
                end
            end

            def live_connected(header) =
                dj = last_authenticated_dj()
                log("DJ Source connected! Last authenticated DJ: #{dj} - #{header}")

                live_enabled.set(true)
                live_dj.set(dj)

                _ = azuracast_api_call(
                    timeout=5.0,
                    "djon",
                    json.stringify({user = dj})
                )
            end

            def live_disconnected() =
                _ = azuracast_api_call(
                    timeout=5.0,
                    "djoff",
                    json.stringify({user = live_dj()})
                )

                live_enabled.set(false)
                live_dj.set("")
            end
            LIQ
        );

        $harborParams = [
            '"' . self::cleanUpString($djMount) . '"',
            'id = "input_streamer"',
            'port = ' . $this->liquidsoap->getStreamPort($station),
            'auth = dj_auth',
            'icy = true',
            'icy_metadata_charset = "' . $charset . '"',
            'metadata_charset = "' . $charset . '"',
            'on_connect = live_connected',
            'on_disconnect = live_disconnected',
        ];

        $djBuffer = $settings->getDjBuffer();
        if (0 !== $djBuffer) {
            $harborParams[] = 'buffer = ' . self::toFloat($djBuffer);
            $harborParams[] = 'max = ' . self::toFloat(max($djBuffer + 5, 10));
        }

        $harborParams = implode(', ', $harborParams);

        $liveBroadcastText = self::cleanUpString(
            $settings->getLiveBroadcastText()
        );

        $event->appendBlock(
            <<<LIQ
            # A Pre-DJ source of radio that can be broadcast if needed
            radio_without_live = radio
            ignore(radio_without_live)

            # Live Broadcasting
            live = input.harbor({$harborParams})

            def insert_missing(m) =
                if m == [] then
                    [("title", "{$liveBroadcastText}"), ("is_live", "true")]
                else
                    [("is_live", "true")]
                end
            end
            live = metadata.map(insert_missing, live)

            radio = fallback(id="live_fallback", track_sensitive=false, replay_metadata=true, [live, radio])

            # Skip non-live track when live DJ goes live.
            def check_live() =
                if live.is_ready() then
                    if not to_live() then
                        to_live.set(true)
                        radio_without_live.skip()
                    end
                else
                    to_live.set(false)
                end
            end

            # Continuously check on live.
            radio = source.on_frame(radio, check_live)
            LIQ
        );

        if ($recordLiveStreams) {
            $recordLiveStreamsFormat = $settings->getRecordStreamsFormatEnum();
            $recordLiveStreamsBitrate = $settings->getRecordStreamsBitrate();

            $formatString = $this->getOutputFormatString($recordLiveStreamsFormat, $recordLiveStreamsBitrate);
            $recordExtension = $recordLiveStreamsFormat->getExtension();
            $recordBasePath = self::cleanUpString($station->getRadioTempDir());
            $recordPathPrefix = StationStreamerBroadcast::PATH_PREFIX;

            $event->appendBlock(
                <<< LIQ
                # Record Live Broadcasts
                recording_base_path = "{$recordBasePath}"
                recording_extension = "{$recordExtension}"

                output.file(
                    {$formatString},
                    fun () -> begin
                        if (live_enabled()) then
                            time.string("#{recording_base_path}/#{live_dj()}/{$recordPathPrefix}_%Y%m%d-%H%M%S.#{recording_extension}.tmp")
                        else
                            ""
                        end
                    end,
                    live,
                    fallible=true,
                    on_close=fun (tempPath) -> begin
                        path = string.replace(pattern=".tmp$", (fun(_) -> ""), tempPath)

                        log("Recording stopped: Switching from #{tempPath} to #{path}")

                        process.run("mv #{tempPath} #{path}")
                        ()
                    end
                )
                LIQ
            );
        }
    }

    public function writePreBroadcastConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();
        $settings = $station->getBackendConfig();

        $event->appendBlock(
            <<<LIQ
            # Allow for Telnet-driven insertion of custom metadata.
            radio = server.insert_metadata(id="custom_metadata", radio)
            LIQ
        );

        if ($settings->isAudioProcessingEnabled() && $settings->getPostProcessingIncludeLive()) {
            $this->writePostProcessingSection($event);
        }

        // Write fallback to safety file to ensure infallible source for the broadcast outputs.
        $errorFile = $this->fallbackFile->getFallbackPathForStation($station);

        $event->appendBlock(
            <<<LIQ
            error_file = single(id="error_jingle", "{$errorFile}")

            def tag_error_file(m) =
                ignore(m)
                [("is_error_file", "true")]
            end
            error_file = metadata.map(tag_error_file, error_file)

            radio = fallback(id="safe_fallback", track_sensitive = false, [radio, error_file])
            LIQ
        );

        $event->appendBlock(
            <<<LIQ
            # Send metadata changes back to AzuraCast
            last_title = ref("")
            last_artist = ref("")

            def metadata_updated(m) =
                def f() =
                    if (m["is_error_file"] != "true") then
                        if (m["title"] != last_title() or m["artist"] != last_artist()) then
                            last_title.set(m["title"])
                            last_artist.set(m["artist"])

                            j = json()

                            if (m["song_id"] != "") then
                                j.add("song_id", m["song_id"])
                                j.add("media_id", m["media_id"])
                                j.add("playlist_id", m["playlist_id"])
                            else
                                j.add("artist", m["artist"])
                                j.add("title", m["title"])
                            end

                            _ = azuracast_api_call(
                                "feedback",
                                json.stringify(j)
                            )
                        end
                    end
                end

                thread.run(f)
            end

            radio.on_metadata(metadata_updated)

            # Handle "Jingle Mode" tracks by replaying the previous metadata.
            last_metadata = ref([])
            def handle_jingle_mode(m) =
                if (m["jingle_mode"] == "true") then
                    last_metadata()
                else
                    last_metadata.set(m)
                    m
                end
            end

            radio = metadata.map(update=false, strip=true, handle_jingle_mode, radio)
            LIQ
        );

        // Custom configuration
        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_PRE_BROADCAST);
    }

    public function writeLocalBroadcastConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();

        if (FrontendAdapters::Remote === $station->getFrontendType()) {
            return;
        }

        $lsConfig = [
            '# Local Broadcasts',
        ];

        // Configure the outbound broadcast.
        $i = 0;
        foreach ($station->getMounts() as $mountRow) {
            $i++;

            /** @var StationMount $mountRow */
            if (!$mountRow->getEnableAutodj()) {
                continue;
            }

            $lsConfig[] = $this->getOutputString($station, $mountRow, 'local_', $i);
        }

        $event->appendLines($lsConfig);
    }

    public function writeHlsBroadcastConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();

        if (!$station->getEnableHls()) {
            return;
        }

        $lsConfig = [
            '# HLS Broadcasting',
        ];

        // Configure the outbound broadcast.
        $hlsStreams = [];

        foreach ($station->getHlsStreams() as $hlsStream) {
            $streamVarName = self::cleanUpVarName($hlsStream->getName());

            if (StreamFormats::Aac !== $hlsStream->getFormat()) {
                continue;
            }

            $streamBitrate = $hlsStream->getBitrate() ?? 128;

            $lsConfig[] = <<<LIQ
            {$streamVarName} = %ffmpeg(
                format="mpegts",
                %audio(
                    codec="aac",
                    samplerate=44100,
                    channels=2,
                    b="{$streamBitrate}k",
                    profile="aac_low"
                )
            )
            LIQ;

            $hlsStreams[] = $streamVarName;
        }

        if (empty($hlsStreams)) {
            return;
        }

        $lsConfig[] = 'hls_streams = [' . implode(
            ', ',
            array_map(
                static fn($row) => '("' . $row . '", ' . $row . ')',
                $hlsStreams
            )
        ) . ']';

        $event->appendLines($lsConfig);

        $configDir = $station->getRadioConfigDir();
        $hlsBaseDir = $station->getRadioHlsDir();
        $tempDir = $station->getRadioTempDir();

        $backendConfig = $station->getBackendConfig();
        $hlsSegmentLength = $backendConfig->getHlsSegmentLength();
        $hlsSegmentsInPlaylist = $backendConfig->getHlsSegmentsInPlaylist();
        $hlsSegmentsOverhead = $backendConfig->getHlsSegmentsOverhead();

        $event->appendBlock(
            <<<LIQ
            def hls_segment_name(~position,~extname,stream_name) =
                timestamp = int_of_float(time())
                duration = {$hlsSegmentLength}
                "#{stream_name}_#{duration}_#{timestamp}_#{position}.#{extname}"
            end

            output.file.hls(playlist="live.m3u8",
                segment_duration={$hlsSegmentLength}.0,
                segments={$hlsSegmentsInPlaylist},
                segments_overhead={$hlsSegmentsOverhead},
                segment_name=hls_segment_name,
                persist_at="{$configDir}/hls.config",
                temp_dir="{$tempDir}",
                "{$hlsBaseDir}",
                hls_streams,
                radio
            )
            LIQ
        );
    }

    /**
     * Given outbound broadcast information, produce a suitable LiquidSoap configuration line for the stream.
     */
    private function getOutputString(
        Station $station,
        StationMountInterface $mount,
        string $idPrefix,
        int $id
    ): string {
        $charset = $station->getBackendConfig()->getCharset();

        $format = $mount->getAutodjFormat() ?? StreamFormats::default();
        $outputFormat = $this->getOutputFormatString(
            $format,
            $mount->getAutodjBitrate() ?? 128
        );

        $outputParams = [];
        $outputParams[] = $outputFormat;
        $outputParams[] = 'id="' . $idPrefix . $id . '"';

        $outputParams[] = 'host = "' . self::cleanUpString($mount->getAutodjHost()) . '"';
        $outputParams[] = 'port = ' . (int)$mount->getAutodjPort();

        $username = $mount->getAutodjUsername();
        if (!empty($username)) {
            $outputParams[] = 'user = "' . self::cleanUpString($username) . '"';
        }

        $password = self::cleanUpString($mount->getAutodjPassword());

        $adapterType = $mount->getAutodjAdapterType();
        if (FrontendAdapters::Shoutcast === $adapterType) {
            $password .= ':#' . $id;
        }

        $outputParams[] = 'password = "' . $password . '"';

        $protocol = $mount->getAutodjProtocol();

        $mountPoint = $mount->getAutodjMount();

        if (StreamProtocols::Icy === $protocol) {
            if (!empty($mountPoint)) {
                $outputParams[] = 'icy_id = ' . $id;
            }
        } else {
            if (empty($mountPoint)) {
                $mountPoint = '/';
            }

            $outputParams[] = 'mount = "' . self::cleanUpString($mountPoint) . '"';
        }

        $outputParams[] = 'name = "' . self::cleanUpString($station->getName()) . '"';

        if (!$mount->getIsShoutcast()) {
            $outputParams[] = 'description = "' . self::cleanUpString($station->getDescription()) . '"';
        }
        $outputParams[] = 'genre = "' . self::cleanUpString($station->getGenre()) . '"';

        if (!empty($station->getUrl())) {
            $outputParams[] = 'url = "' . self::cleanUpString($station->getUrl()) . '"';
        }

        $outputParams[] = 'public = ' . ($mount->getIsPublic() ? 'true' : 'false');
        $outputParams[] = 'encoding = "' . $charset . '"';

        if (StreamProtocols::Https === $protocol) {
            $outputParams[] = 'transport=https_transport';
        }

        if ($format->sendIcyMetadata()) {
            $outputParams[] = 'send_icy_metadata=true';
        }

        $outputParams[] = 'radio';

        $outputCommand = ($mount->getIsShoutcast())
            ? 'output.shoutcast'
            : 'output.icecast';

        return $outputCommand . '(' . implode(', ', $outputParams) . ')';
    }

    private function getOutputFormatString(StreamFormats $format, int $bitrate = 128): string
    {
        switch ($format) {
            case StreamFormats::Aac:
                $afterburner = ($bitrate >= 160) ? 'true' : 'false';
                $aot = ($bitrate >= 96) ? 'mpeg4_aac_lc' : 'mpeg4_he_aac_v2';

                return '%fdkaac(channels=2, samplerate=44100, bitrate=' . $bitrate . ', afterburner=' . $afterburner . ', aot="' . $aot . '", sbr_mode=true)';

            case StreamFormats::Ogg:
                return '%vorbis.cbr(samplerate=44100, channels=2, bitrate=' . $bitrate . ')';

            case StreamFormats::Opus:
                return '%opus(samplerate=48000, bitrate=' . $bitrate . ', vbr="constrained", application="audio", channels=2, signal="music", complexity=10, max_bandwidth="full_band")';

            case StreamFormats::Flac:
                return '%ogg(%flac(samplerate=48000, channels=2, compression=4, bits_per_sample=24))';

            case StreamFormats::Mp3:
                return '%mp3(samplerate=44100, stereo=true, bitrate=' . $bitrate . ')';

            default:
                throw new RuntimeException(sprintf('Unsupported stream format: %s', $format->value));
        }
    }

    public function writeRemoteBroadcastConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $station = $event->getStation();

        $lsConfig = [
            '# Remote Relays',
        ];

        // Set up broadcast to remote relays.
        $i = 0;
        foreach ($station->getRemotes() as $remoteRow) {
            $i++;

            /** @var StationRemote $remoteRow */
            if (!$remoteRow->getEnableAutodj()) {
                continue;
            }

            $lsConfig[] = $this->getOutputString($station, $remoteRow, 'relay_', $i);
        }

        $event->appendLines($lsConfig);
    }

    public function writePostBroadcastConfiguration(WriteLiquidsoapConfiguration $event): void
    {
        $this->writeCustomConfigurationSection($event, StationBackendConfiguration::CUSTOM_BOTTOM);
    }

    /**
     * Convert an integer or float into a Liquidsoap configuration compatible float.
     *
     * @param float|int|string $number
     * @param int $decimals
     */
    public static function toFloat(float|int|string $number, int $decimals = 2): string
    {
        return number_format((float)$number, $decimals, '.', '');
    }

    public static function formatTimeCode(int $timeCode): string
    {
        $hours = floor($timeCode / 100);
        $mins = $timeCode % 100;

        return $hours . 'h' . $mins . 'm';
    }

    /**
     * Filter a user-supplied string to be a valid LiquidSoap config entry.
     *
     * @param string|null $string
     *
     */
    public static function cleanUpString(?string $string): string
    {
        return str_replace(['"', "\n", "\r"], ['\'', '', ''], $string ?? '');
    }

    /**
     * Apply a more aggressive string filtering to variable names used in Liquidsoap.
     *
     * @param string $str
     *
     * @return string The cleaned up, variable-name-friendly string.
     */
    public static function cleanUpVarName(string $str): string
    {
        $str = strtolower(
            preg_replace(
                ['/[\r\n\t ]+/', '/[\"*\/:<>?\'|]+/'],
                ' ',
                strip_tags($str)
            ) ?? ''
        );
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str) ?? '';
        $str = rawurlencode(str_replace(' ', '_', $str));
        return str_replace(['%', '-', '.'], ['', '_', '_'], $str);
    }

    public static function getPlaylistVariableName(StationPlaylist $playlist): string
    {
        return self::cleanUpVarName('playlist_' . $playlist->getShortName());
    }

    public static function annotateString(string $str): string
    {
        $str = mb_convert_encoding($str, 'UTF-8');
        return str_replace(['"', "\n", "\t", "\r"], ['\"', '', '', ''], $str);
    }
}
