<template>
    <section
        id="content"
        role="main"
        style="flex: 1"
    >
        <div class="container pt-5">
            <div class="row g-3">
                <div class="col-md-4 mb-sm-4">
                    <settings-panel :station-name="stationName" />
                </div>

                <div class="col-md-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <microphone-panel />
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <mixer-panel />
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 mb-sm-4">
                            <playlist-panel id="playlist_1" />
                        </div>

                        <div class="col-md-6">
                            <playlist-panel id="playlist_2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import MixerPanel from "~/components/Public/WebDJ/MixerPanel.vue";
import MicrophonePanel from "~/components/Public/WebDJ/MicrophonePanel.vue";
import PlaylistPanel from "~/components/Public/WebDJ/PlaylistPanel.vue";
import SettingsPanel from "~/components/Public/WebDJ/SettingsPanel.vue";
import {useProvideWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";
import {useProvideWebcaster, WebcasterProps} from "~/components/Public/WebDJ/useWebcaster";
import {useProvideMixer} from "~/components/Public/WebDJ/useMixerValue";
import {useProvidePassthroughSync} from "~/components/Public/WebDJ/usePassthroughSync";

interface WebDjProps extends WebcasterProps {
    stationName: string | null
}

const props = defineProps<WebDjProps>();

const webcaster = useProvideWebcaster(props);

useProvideWebDjNode(webcaster);

useProvideMixer(1.0);

useProvidePassthroughSync('');
</script>
