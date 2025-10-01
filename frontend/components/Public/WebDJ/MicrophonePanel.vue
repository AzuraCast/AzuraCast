<template>
    <div class="card">
        <div class="card-header text-bg-primary">
            <div class="d-flex align-items-center">
                <div class="flex-fill">
                    <h5 class="card-title">
                        {{ $gettext('Microphone') }}
                    </h5>
                </div>
                <div class="flex-shrink-0 ps-3">
                    <volume-slider v-model.number="trackGain" />
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="d-flex-shrink-0">
                    <div class="control-group">
                        <div class="btn-group btn-group-sm">
                            <button
                                type="button"
                                class="btn btn-danger"
                                :class="{ active: isPlaying }"
                                @click="togglePlaying"
                                :title="(isPlaying) ? $gettext('Disable Microphone') : $gettext('Enable Microphone')"
                                :aria-label="(isPlaying) ? $gettext('Disable Microphone') : $gettext('Enable Microphone')"
                            >
                                <icon-ic-mic/>
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="{ 'btn-primary': trackPassThrough }"
                                @click="trackPassThrough = !trackPassThrough"
                            >
                                {{ $gettext('Cue') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex-fill ps-3">
                    <div class="form-group microphone-entry mb-0">
                        <label
                            for="select_microphone_source"
                            class="mb-2"
                        >
                            {{ $gettext('Microphone Source') }}
                        </label>
                        <div class="controls">
                            <select
                                id="select_microphone_source"
                                v-model="device"
                                class="form-control"
                            >
                                <option
                                    v-for="device_row in audioInputs"
                                    :key="device_row.deviceId"
                                    :value="device_row.deviceId"
                                >
                                    {{ device_row.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="isPlaying"
                class="mt-3"
            >
                <div class="progress mb-2">
                    <div
                        class="progress-bar"
                        :style="{ width: volume+'%' }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import VolumeSlider from "~/components/Public/WebDJ/VolumeSlider.vue";
import {useDevicesList} from "@vueuse/core";
import {ref, watch} from "vue";
import {useWebDjTrack} from "~/components/Public/WebDJ/useWebDjTrack";
import {usePassthroughSync} from "~/components/Public/WebDJ/usePassthroughSync";
import {useWebDjSource} from "~/components/Public/WebDJ/useWebDjSource";
import IconIcMic from "~icons/ic/baseline-mic";

const {
    source,
    isPlaying,
    trackGain,
    trackPassThrough,
    volume,
    prepare,
    stop
} = useWebDjTrack();

const {
    createMicrophoneSource
} = useWebDjSource();

usePassthroughSync(trackPassThrough, 'microphone');

const {audioInputs} = useDevicesList({
    requestPermissions: true,
    constraints: {audio: true, video: false}
});

const device = ref<string | null>(null);
watch(audioInputs, (inputs) => {
    if (device.value === null) {
        device.value = inputs[0]?.deviceId;
    }
});

let destination: AudioNode | null = null;

const createSource = () => {
    if (destination === null) {
        return;
    }

    if (source.value != null) {
        source.value.disconnect(destination);
    }

    if (device.value) {
        createMicrophoneSource(device.value, (newSource) => {
            source.value = newSource;
            if (destination !== null) {
                newSource.connect(destination);
            }
        });
    }
};

watch(device, () => {
    if (source.value === null || destination === null) {
        return;
    }
    createSource();
});

const play = () => {
    destination = prepare();
    createSource();
}

const togglePlaying = () => {
    if (isPlaying.value) {
        stop();
    } else {
        play();
    }
}
</script>
