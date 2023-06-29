<template>
    <section
        id="content"
        role="main"
        style="height: 100vh;"
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

<script setup>
import MixerPanel from './WebDJ/MixerPanel.vue';
import MicrophonePanel from './WebDJ/MicrophonePanel.vue';
import PlaylistPanel from './WebDJ/PlaylistPanel.vue';
import SettingsPanel from './WebDJ/SettingsPanel.vue';
import {useProvideWebDjNode, useWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";
import {ref} from "vue";
import {useProvideWebcaster, useWebcaster, webcasterProps} from "~/components/Public/WebDJ/useWebcaster";
import {useProvideMixer} from "~/components/Public/WebDJ/useMixerValue";
import {useProvidePassthroughSync} from "~/components/Public/WebDJ/usePassthroughSync";

const props = defineProps({
    ...webcasterProps,
    stationName: {
        type: String,
        required: true
    },
});

const webcaster = useWebcaster(props);
useProvideWebcaster(webcaster);

const node = useWebDjNode(webcaster);
useProvideWebDjNode(node);

const mixer = ref(1.0);
useProvideMixer(mixer);

const passthroughSync = ref('');
useProvidePassthroughSync(passthroughSync);
</script>
