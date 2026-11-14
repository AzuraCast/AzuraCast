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
                    <volume-slider v-model.number="gain"/>
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
                                :class="{ active: isActive }"
                                @click="isActive ? start() : stop()"
                                :title="(isActive) ? $gettext('Disable Microphone') : $gettext('Enable Microphone')"
                                :aria-label="(isActive) ? $gettext('Disable Microphone') : $gettext('Enable Microphone')"
                            >
                                <icon-ic-mic/>
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="{ 'btn-primary': isCued }"
                                @click="toggleCue()"
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
                v-if="isActive"
                class="mt-3"
            >
                <div class="progress mb-2">
                    <div
                        class="progress-bar"
                        :style="{ width: leftLevel+'%' }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    useInjectWebcasterMicrophone,
    useProvideWebcasterMicrophone,
} from "~/components/Public/WebDJ/useWebcasterMicrophone.ts";
import VolumeSlider from "~/components/Public/WebDJ/VolumeSlider.vue";
import IconIcMic from "~icons/ic/baseline-mic";

useProvideWebcasterMicrophone();

const {
    gain,
    leftLevel,
    toggleCue,
    isActive,
    isCued,
    audioInputs,
    device,
    stop,
    start,
} = useInjectWebcasterMicrophone();
</script>
