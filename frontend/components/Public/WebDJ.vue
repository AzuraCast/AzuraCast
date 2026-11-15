<template>
    <section
        id="content"
        role="main"
        style="flex: 1"
    >
        <div class="container pt-5">
            <div class="row g-3">
                <div class="col-md-4 mb-sm-4">
                    <settings-panel :station-name="props.stationName" :base-uri="props.baseUri"/>
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
                            <playlist-panel side="left"/>
                        </div>

                        <div class="col-md-6">
                            <playlist-panel side="right"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import MicrophonePanel from "~/components/Public/WebDJ/MicrophonePanel.vue";
import MixerPanel from "~/components/Public/WebDJ/MixerPanel.vue";
import PlaylistPanel from "~/components/Public/WebDJ/PlaylistPanel.vue";
import SettingsPanel from "~/components/Public/WebDJ/SettingsPanel.vue";
import { useProvideWebcasterMixer } from "~/components/Public/WebDJ/useWebcasterMixer.ts";

type WebDjProps = {
    stationName: string | null;
    baseUri: string;
};

const props = defineProps<WebDjProps>();

useProvideWebcasterMixer();
</script>
