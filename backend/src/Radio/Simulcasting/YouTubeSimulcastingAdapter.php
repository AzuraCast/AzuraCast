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
            'type' => 'rtmp',
            'stream_key' => $this->getStreamKey(),
            'format' => 'flv',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
            'url' => 'rtmp://a.rtmp.youtube.com/live2/',
        ];
    }

    public function getLiquidsoapOutput(Simulcasting $simulcasting, \App\Entity\Station $station): string
    {
        $config = $this->getConfiguration();
        $outputName = "simulcast_youtube_{$this->getCleanStreamName($simulcasting)}_{$simulcasting->getId()}";
        $instanceId = $simulcasting->getId();
        
        return <<<LIQ
        # YouTube Live (RTMP) - Controllable source
        # Stefan from Liquidsoap IRC is a fucking GOD!
        retry_count_{$outputName} = ref(0)
        {$outputName} = output.url(
            id="{$outputName}",
            url="{$config['url']}{$config['stream_key']}",
            start=false,
            fallible=true,
            restart_delay=0.3,
            %ffmpeg(
                format="flv",
                %video(
                    codec="libx264",
                    pixel_format="yuv420p",
                    b=simulcast_v_bps,
                    preset="superfast",
                    r=simulcast_v_fps,
                    g=simulcast_v_gop
                ),
                %audio(
                    codec="aac",
                    samplerate=44100,
                    channels=2,
                    b=simulcast_a_bps,
                    profile="aac_low"
                )
            ),
            simulcast_videostream,
            # Callbacks
            on_start = fun() -> begin
                retry_count_{$outputName} := 0
                azuracast.simulcast_notify([
                    ("instance_id", "{$instanceId}"),
                    ("event", "started")
                ])
            end,
            on_stop = fun() -> begin
                azuracast.simulcast_notify([
                    ("instance_id", "{$instanceId}"),
                    ("event", "stopped")
                ])
            end,
            on_error = fun(e) -> begin
                retry_count_{$outputName} := retry_count_{$outputName}() + 1
                if retry_count_{$outputName}() >= 3 then 
                     ignore(server.execute("{$outputName}_stop"))
                end
                azuracast.simulcast_notify([
                    ("instance_id", "{$instanceId}"),
                    ("event", "errored"),
                    ("reason", e.message)
                ])
            end
        )

        # Telnet aliases to control it
        server.register(
        "{$outputName}_start",
        fun(_) -> begin
            {$outputName}.start();     # <- call with ()
            "OK"
        end
        )

        server.register(
        "{$outputName}_stop",
        fun(_) -> begin
            {$outputName}.stop();      # <- call with ()
            "OK"
        end
        )

        server.register(
        "{$outputName}_active",
        fun(_) ->
            if {$outputName}.is_active() then "true" else "false" end
        )

        server.register(
        "{$outputName}_status",
        fun(_) ->
            "id=" ^ {$outputName}.id()
            ^ " active="  ^ (if {$outputName}.is_active()  then "true" else "false" end)
            ^ " up="      ^ (if {$outputName}.is_up()      then "true" else "false" end)
            ^ " started=" ^ (if {$outputName}.is_started() then "true" else "false" end)
            ^ " ready="   ^ (if {$outputName}.is_ready()   then "true" else "false" end)
            ^ ""
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
