<template>
    <div class="card settings">
        <div class="card-header text-bg-primary">
            <h5 class="card-title">
                {{ $gettext('WebDJ') }}
                <br>
                <small>{{ stationName }}</small>
            </h5>
        </div>
        <template v-if="isConnected">
            <div class="card-body">
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
                        >
                    </div>
                </div>
                <div class="form-group">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="updateMetadata"
                    >
                        {{ $gettext('Update Metadata') }}
                    </button>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="card-body alert-info">
                <p class="card-text">
                    {{ $gettext('The WebDJ lets you broadcast live to your station using just your web browser.') }}
                </p>
                <p class="card-text">
                    {{
                        $gettext('To use this feature, a secure (HTTPS) connection is required. Firefox is recommended to avoid static when broadcasting.')
                    }}
                </p>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col md-6">
                        <div class="form-group">
                            <label
                                for="dj_username"
                                class="mb-2"
                            >
                                {{ $gettext('Username') }}
                            </label>
                            <div class="controls">
                                <input
                                    id="dj_username"
                                    v-model="djUsername"
                                    type="text"
                                    class="form-control"
                                >
                            </div>
                        </div>
                    </div>
                    <div class="col md-6">
                        <div class="form-group">
                            <label
                                for="dj_password"
                                class="mb-2"
                            >
                                {{ $gettext('Password') }}
                            </label>
                            <div class="controls">
                                <input
                                    id="dj_password"
                                    v-model="djPassword"
                                    type="password"
                                    class="form-control"
                                >
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col md-6">
                        <div class="form-group">
                            <label
                                for="select_samplerate"
                                class="mb-2"
                            >
                                {{ $gettext('Sample Rate') }}
                            </label>
                            <div class="controls">
                                <select
                                    id="select_samplerate"
                                    v-model.number="sampleRate"
                                    class="form-control"
                                >
                                    <option value="8000">
                                        8 kHz
                                    </option>
                                    <option value="11025">
                                        11.025 kHz
                                    </option>
                                    <option value="12000">
                                        12 kHz
                                    </option>
                                    <option value="16000">
                                        16 kHz
                                    </option>
                                    <option value="22050">
                                        22.05 kHz
                                    </option>
                                    <option value="24000">
                                        24 kHz
                                    </option>
                                    <option value="32000">
                                        32 kHz
                                    </option>
                                    <option value="44100">
                                        44.1 kHz
                                    </option>
                                    <option value="48000">
                                        48 kHz
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col md-6">
                        <div class="form-group">
                            <label
                                for="select_bitrate"
                                class="mb-2"
                            >
                                {{ $gettext('Bit Rate') }}
                            </label>
                            <div class="controls">
                                <select
                                    id="select_bitrate"
                                    v-model.number="bitrate"
                                    class="form-control"
                                >
                                    <option value="8">
                                        8 kbps
                                    </option>
                                    <option value="16">
                                        16 kbps
                                    </option>
                                    <option value="24">
                                        24 kbps
                                    </option>
                                    <option value="32">
                                        32 kbps
                                    </option>
                                    <option value="40">
                                        40 kbps
                                    </option>
                                    <option value="48">
                                        48 kbps
                                    </option>
                                    <option value="56">
                                        56 kbps
                                    </option>
                                    <option value="64">
                                        64 kbps
                                    </option>
                                    <option value="80">
                                        80 kbps
                                    </option>
                                    <option value="96">
                                        96 kbps
                                    </option>
                                    <option value="112">
                                        112 kbps
                                    </option>
                                    <option value="128">
                                        128 kbps
                                    </option>
                                    <option value="144">
                                        144 kbps
                                    </option>
                                    <option value="160">
                                        160 kbps
                                    </option>
                                    <option value="192">
                                        192 kbps
                                    </option>
                                    <option value="224">
                                        224 kbps
                                    </option>
                                    <option value="256">
                                        256 kbps
                                    </option>
                                    <option value="320">
                                        320 kbps
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="card-body">
            <button
                v-if="!isConnected"
                type="button"
                class="btn btn-success"
                @click="startStream(djUsername, djPassword)"
            >
                {{ langStreamButton }}
            </button>
            <button
                v-if="isConnected"
                type="button"
                class="btn btn-danger"
                @click="stopStream"
            >
                {{ langStreamButton }}
            </button>
            <button
                type="button"
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
import {computed, ref, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useInjectWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";
import {usePassthroughSync} from "~/components/Public/WebDJ/usePassthroughSync";
import {useInjectWebcaster} from "~/components/Public/WebDJ/useWebcaster";

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
    bitrate,
    sampleRate,
    startStream,
    stopStream
} = useInjectWebDjNode();

const {
    metadata,
    sendMetadata,
    isConnected
} = useInjectWebcaster();

usePassthroughSync(doPassThrough, 'global');

const {$gettext} = useTranslate();

const langStreamButton = computed(() => {
    return (isConnected.value)
        ? $gettext('Stop Streaming')
        : $gettext('Start Streaming');
});

const shownMetadata = ref({});
watch(metadata, (newMeta) => {
    if (newMeta === null) {
        newMeta = {
            artist: '',
            title: ''
        };
    }

    shownMetadata.value = newMeta;
});

const updateMetadata = () => {
    sendMetadata(shownMetadata.value);
};
</script>
