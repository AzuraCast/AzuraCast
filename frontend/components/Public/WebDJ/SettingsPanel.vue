<template>
    <div class="card settings">
        <div class="card-header text-bg-primary">
            <h5 class="card-title">
                {{ $gettext('Web DJ') }}
                <br>
                <small>{{ props.stationName }}</small>
            </h5>
        </div>
        <template v-if="isIdle">
            <div class="card-body alert-info">
                <p class="card-text">
                    {{ $gettext('The WebDJ lets you broadcast live to your station using just your web browser.') }}
                </p>
                <p class="card-text">
                    {{
                        $gettext('To use this feature, a secure (HTTPS) connection is required.')
                    }}
                </p>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
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
                    <div class="col-md-6">
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
                    <div class="col-md-6">
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
            <div
                v-if="!isIdle"
                class="form-group"
            >
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="isEmptyMetadata(shownMetadata)"
                    @click="updateMetadata"
                >
                    {{ $gettext('Update Metadata') }}
                </button>
            </div>
        </div>

        <div class="card-body">
            <button
                v-if="isIdle"
                type="button"
                class="btn btn-success"
                @click="startStreamButton"
            >
                {{ langStreamButton }}
            </button>
            <button
                v-if="!isIdle"
                type="button"
                class="btn btn-danger"
                @click="stopStream"
            >
                {{ langStreamButton }}
            </button>
            <button
                type="button"
                class="btn"
                :class="{ 'btn-primary': isGlobalCued }"
                @click="toggleGlobalCue"
            >
                {{ $gettext('Cue') }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
    isEmptyMetadata,
    useInjectWebcasterMixer,
    WebcasterMetadata,
} from "~/components/Public/WebDJ/useWebcasterMixer.ts";
import { useTranslate } from "~/vendor/gettext";

const props = defineProps<{
    stationName: string | null;
    baseUri: string;
}>();

const djUsername = ref<string | null>(null);
const djPassword = ref<string | null>(null);

const {
    isIdle,
    stopStream,
    startStream,
    toggleGlobalCue,
    isGlobalCued,
    currentMetadata,
    sendMetadata,
} = useInjectWebcasterMixer();

const bitrate = ref<number>(128);

const startStreamButton = () => {
    currentMetadata.value = { ...shownMetadata.value };

    const url = new URL(props.baseUri);
    url.username = encodeURIComponent(djUsername.value ?? "");
    url.password = encodeURIComponent(djPassword.value ?? "");

    startStream({
        url: url.toString(),
        mimeType: "audio/webm;codecs=opus",
        channels: 2,
        audioBitrate: bitrate.value,
    });
};

const { $gettext } = useTranslate();

const langStreamButton = computed(() => {
    return !isIdle.value
        ? $gettext("Stop Streaming")
        : $gettext("Start Streaming");
});

const shownMetadata = ref<WebcasterMetadata>({
    artist: "",
    title: "",
});

watch(currentMetadata, (newMeta) => (shownMetadata.value = { ...newMeta }));

const updateMetadata = () => {
    sendMetadata(shownMetadata.value);
};
</script>
