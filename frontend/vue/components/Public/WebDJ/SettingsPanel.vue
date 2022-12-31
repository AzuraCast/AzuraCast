<template>
    <div class="card settings">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                {{ $gettext('WebDJ') }}
                <br>
                <small>{{ stationName }}</small>
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="form-row pb-4">
                <div class="col-sm-12">
                    <ul class="nav nav-tabs card-header-tabs mt-0">
                        <li class="nav-item">
                            <a
                                class="nav-link active"
                                href="#settings"
                                data-toggle="tab"
                            >
                                {{ $gettext('Settings') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#metadata"
                                data-toggle="tab"
                            >
                                {{ $gettext('Metadata') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="form-row">
                <div class="col-sm-12">
                    <div class="tab-content mt-1">
                        <div
                            id="settings"
                            class="tab-pane active"
                        >
                            <div class="form-group">
                                <label class="mb-2">
                                    {{ $gettext('DJ Credentials') }}
                                </label>

                                <div class="form-row">
                                    <div class="col-6">
                                        <input
                                            v-model="djUsername"
                                            type="text"
                                            class="form-control"
                                            :placeholder="$gettext('Username')"
                                        >
                                    </div>
                                    <div class="col-6">
                                        <input
                                            v-model="djPassword"
                                            type="password"
                                            class="form-control"
                                            :placeholder="$gettext('Password')"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            id="metadata"
                            class="tab-pane"
                        >
                            <div class="form-group">
                                <label
                                    for="metadata_title"
                                    class="mb-2"
                                >
                                    {{ $gettext('Title') }}
                                </label>
                                <div class="controls">
                                    <input
                                        id="metadata_title"
                                        v-model="shownMetadata.title"
                                        class="form-control"
                                        type="text"
                                        :disabled="!isStreaming"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label
                                    for="metadata_artist"
                                    class="mb-2"
                                >
                                    {{ $gettext('Artist') }}
                                </label>
                                <div class="controls">
                                    <input
                                        id="metadata_artist"
                                        v-model="shownMetadata.artist"
                                        class="form-control"
                                        type="text"
                                        :disabled="!isStreaming"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <button
                                    class="btn btn-primary"
                                    :disabled="!isStreaming"
                                    @click="updateMetadata"
                                >
                                    {{ $gettext('Update Metadata') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-actions">
            <button
                v-if="!isStreaming"
                class="btn btn-success"
                @click="startStream(djUsername, djPassword)"
            >
                {{ langStreamButton }}
            </button>
            <button
                v-if="isStreaming"
                class="btn btn-danger"
                @click="stopStream"
            >
                {{ langStreamButton }}
            </button>
            <button
                class="btn"
                :class="{ 'btn-primary': doPassThrough }"
                @click="doPassThrough = !doPassThrough"
            >
                {{ $gettext('Cue') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import {computed, inject, ref} from "vue";
import {syncRef} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    stationName: {
        type: String,
        required: true
    }
});

const djUsername = ref(null);
const djPassword = ref(null);

const {
    doPassThrough,
    isStreaming,
    startStream,
    stopStream,
    metadata,
    sendMetadata
} = inject('node');

const {$gettext} = useTranslate();

const langStreamButton = computed(() => {
    return (isStreaming.value)
        ? $gettext('Stop Streaming')
        : $gettext('Start Streaming');
});

const shownMetadata = ref({});
syncRef(metadata, shownMetadata, {direction: "ltr"});

const updateMetadata = () => {
    sendMetadata(shownMetadata.value);
};
</script>
