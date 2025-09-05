<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

use App\Entity\Simulcasting;

class YouTubeSimulcastingAdapter extends AbstractSimulcastingAdapter
{
    public function getStreamKey(): string
    {
        return $this->simulcasting->getStreamKey();
    }

    public function getStatus(): string
    {
        return $this->simulcasting->getStatus()->value;
    }

    public function run(): bool
    {
        // This will be controlled via LiquidSoap configuration
        // The actual implementation is in the LiquidSoap script
        return true;
    }

    public function stop(): bool
    {
        // This will be controlled via LiquidSoap configuration
        return true;
    }

    public function getAdapterName(): string
    {
        return 'youtube';
    }

    public function getAdapterDescription(): string
    {
        return 'YouTube Live';
    }

    public function getConfiguration(): array
    {
        return [
            'type' => 'hls',
            'stream_key' => $this->getStreamKey(),
            'format' => 'mpegts',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ];
    }

    public function getLiquidsoapOutput(Simulcasting $simulcasting, \App\Entity\Station $station): string
    {
        $config = $this->getConfiguration();
        $outputName = "simulcast_youtube_{$this->getCleanStreamName($simulcasting)}_{$simulcasting->getId()}";
        
        return <<<LIQ
        # YouTube Live (HLS ingest) - Controllable source
        {$outputName} = output.youtube.live.hls(
            id="{$outputName}",
            key="{$config['stream_key']}",
            start=false,
            fallible=true,
            %ffmpeg(
                format="{$config['format']}",
                %video.raw(
                    codec="{$config['video_codec']}",
                    pixel_format="yuv420p",
                    b=simulcast_v_bps,
                    preset="veryfast",
                    r=simulcast_v_fps,
                    g=simulcast_v_gop
                ),
                %audio(
                    codec="{$config['audio_codec']}",
                    samplerate=44100,
                    channels=2,
                    b=simulcast_a_bps,
                    profile="aac_low"
                )
            ),
            simulcast_videostream
        )

        # Telnet aliases to control it
        server.register(
        "{$outputName}_start",
        fun(_) -> begin
            {$outputName}.start();     # <- call with ()
            "OK\n"
        end
        )

        server.register(
        "{$outputName}_stop",
        fun(_) -> begin
            {$outputName}.stop();      # <- call with ()
            "OK\n"
        end
        )

        server.register(
        "{$outputName}_active",
        fun(_) ->
            if {$outputName}.is_active() then "true\n" else "false\n" end
        )

        # Build a status string from available fields
        server.register(
        "{$outputName}_status",
        fun(_) ->
            "id=" ^ {$outputName}.id()
            ^ " active="  ^ (if {$outputName}.is_active()  then "true" else "false" end)
            ^ " up="      ^ (if {$outputName}.is_up()      then "true" else "false" end)
            ^ " started=" ^ (if {$outputName}.is_started() then "true" else "false" end)
            ^ " ready="   ^ (if {$outputName}.is_ready()   then "true" else "false" end)
            ^ "\n"
        )
        LIQ;
    }
    
    private function getCleanStreamName(Simulcasting $simulcasting): string
    {
        $streamName = $simulcasting->getName();
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', $streamName);
        return strtolower($cleanName);
    }
}
