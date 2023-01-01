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
            <template v-if="isConnected">
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
                        class="btn btn-primary"
                        @click="updateMetadata"
                    >
                        {{ $gettext('Update Metadata') }}
                    </button>
                </div>
            </template>
            <template v-else>
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
            </template>
        </div>

        <div class="card-actions">
            <button
                v-if="!isConnected"
                class="btn btn-success"
                @click="startStream(djUsername, djPassword)"
            >
                {{ langStreamButton }}
            </button>
            <button
                v-if="isConnected"
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
import {computed, inject, ref, watch} from "vue";
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
    isConnected,
    startStream,
    stopStream,
    metadata,
    sendMetadata
} = inject('node');

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
