<template>
    <div class="card">
        <div class="card-header text-bg-primary">
            <div class="d-flex align-items-center">
                <div class="flex-fill text-nowrap">
                    <h5 class="card-title">
                        {{ langHeader }}
                    </h5>
                </div>
                <div class="flex-shrink-0 ps-3">
                    <volume-slider v-model.number="localGain" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="!isPlaying || isPaused"
                        type="button"
                        class="btn btn-sm btn-success"
                        @click="play"
                    >
                        <icon icon="play_arrow" />
                    </button>
                    <button
                        v-if="isPlaying && !isPaused"
                        type="button"
                        class="btn btn-sm btn-warning"
                        @click="togglePause()"
                    >
                        <icon icon="pause" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        @click="previous()"
                    >
                        <icon icon="fast_rewind" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        @click="next()"
                    >
                        <icon icon="fast_forward" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="stop()"
                    >
                        <icon icon="stop" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="{ 'btn-primary': trackPassThrough }"
                        @click="trackPassThrough = !trackPassThrough"
                    >
                        {{ $gettext('Cue') }}
                    </button>
                </div>
            </div>

            <div
                v-if="isPlaying"
                class="mt-3"
            >
                <div class="d-flex flex-row mb-2">
                    <div class="flex-shrink-0 pt-1 pe-2">
                        {{ formatTime(position) }}
                    </div>
                    <div class="flex-fill">
                        <input
                            v-model="seekingPosition"
                            type="range"
                            min="0"
                            max="100"
                            step="0.1"
                            class="form-range slider"
                            @mousedown="isSeeking = true"
                            @mouseup="isSeeking = false"
                        >
                    </div>
                    <div class="flex-shrink-0 pt-1 ps-2">
                        {{ formatTime(duration) }}
                    </div>
                </div>

                <div class="progress">
                    <div
                        class="progress-bar"
                        :style="{ width: volume+'%' }"
                    />
                </div>
            </div>

            <div class="form-group mt-2">
                <div class="custom-file">
                    <input
                        :id="id + '_files'"
                        type="file"
                        class="custom-file-input files"
                        accept="audio/*"
                        multiple="multiple"
                        @change="addNewFiles($event.target.files)"
                    >
                    <label
                        :for="id + '_files'"
                        class="custom-file-label"
                    >
                        {{ $gettext('Add Files to Playlist') }}
                    </label>
                </div>
            </div>

            <div class="form-group mb-0">
                <div class="controls">
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input
                            :id="id + '_playthrough'"
                            v-model="playThrough"
                            type="checkbox"
                            class="custom-control-input"
                        >
                        <label
                            :for="id + '_playthrough'"
                            class="custom-control-label"
                        >
                            {{ $gettext('Continuous Play') }}
                        </label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input
                            :id="id + '_loop'"
                            v-model="loop"
                            type="checkbox"
                            class="custom-control-input"
                        >
                        <label
                            :for="id + '_loop'"
                            class="custom-control-label"
                        >
                            {{ $gettext('Repeat') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="files.length > 0"
            class="list-group list-group-flush"
        >
            <a
                v-for="(rowFile, rowIndex) in files"
                :key="rowFile.file.name"
                href="#"
                class="list-group-item list-group-item-action flex-column align-items-start"
                :class="{ active: rowIndex === fileIndex }"
                @click.prevent="play({ fileIndex: rowIndex })"
            >
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-0">{{
                        rowFile.metadata?.title ?? $gettext('Unknown Title')
                    }}</h5>
                    <small class="pt-1">{{ formatTime(rowFile.audio.length) }}</small>
                </div>
                <p class="mb-0">{{ rowFile.metadata?.artist ?? $gettext('Unknown Artist') }}</p>
            </a>
        </div>
    </div>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import VolumeSlider from "~/components/Public/WebDJ/VolumeSlider";
import formatTime from "~/functions/formatTime";
import {computed, ref, watch} from "vue";
import {useWebDjTrack} from "~/components/Public/WebDJ/useWebDjTrack";
import {useTranslate} from "~/vendor/gettext";
import {forEach} from "lodash";
import {useInjectMixer} from "~/components/Public/WebDJ/useMixerValue";
import {usePassthroughSync} from "~/components/Public/WebDJ/usePassthroughSync";
import {useWebDjSource} from "~/components/Public/WebDJ/useWebDjSource";
import {useInjectWebcaster} from "~/components/Public/WebDJ/useWebcaster";

const props = defineProps({
    id: {
        type: String,
        required: true
    }
});

const isLeftPlaylist = computed(() => {
    return props.id === 'playlist_1';
});

const {
    source,
    isPlaying,
    isPaused,
    trackGain,
    trackPassThrough,
    position,
    volume,
    prepare,
    togglePause,
    stop
} = useWebDjTrack();

const {
    createAudioSource
} = useWebDjSource();

const {
    sendMetadata
} = useInjectWebcaster();

usePassthroughSync(trackPassThrough, props.id);

const fileIndex = ref(-1);
const files = ref([]);
const duration = ref(0.0);
const loop = ref(false);
const playThrough = ref(false);

const isSeeking = ref(false);

const seekingPosition = computed({
    get: () => {
        return (100.0 * position.value / parseFloat(duration.value));
    },
    set: (val) => {
        if (!isSeeking.value || !source.value) {
            return;
        }

        source.value.seek(val / 100);
    }
});

// Factor in mixer and local gain to calculate total gain.
const localGain = ref(55);
const mixer = useInjectMixer();

const computedGain = computed(() => {
    let multiplier;
    if (isLeftPlaylist.value) {
        multiplier = (mixer.value > 1)
            ? 2.0 - (mixer.value)
            : 1.0;
    } else {
        multiplier = (mixer.value < 1)
            ? mixer.value
            : 1.0;
    }

    return localGain.value * multiplier;
});
watch(computedGain, (newGain) => {
    trackGain.value = newGain;
}, {immediate: true});

const {$gettext} = useTranslate();

const langHeader = computed(() => {
    return isLeftPlaylist.value
        ? $gettext('Playlist 1')
        : $gettext('Playlist 2');
});

const addNewFiles = (newFiles) => {
    forEach(newFiles, (file) => {
        file.readTaglibMetadata((data) => {
            files.value.push({
                file: file,
                audio: data.audio,
                metadata: data.metadata || {title: '', artist: ''}
            });
        });
    });
};

const selectFile = (options = {}) => {
    if (files.value.length === 0) {
        return;
    }

    if (options.fileIndex) {
        fileIndex.value = options.fileIndex;
    } else {
        fileIndex.value += options.backward ? -1 : 1;
        if (fileIndex.value < 0) {
            fileIndex.value = files.value.length - 1;
        }

        if (fileIndex.value >= files.value.length) {
            if (options.isAutoPlay && !loop.value) {
                fileIndex.value = -1;
                return;
            }

            if (fileIndex.value < 0) {
                fileIndex.value = files.value.length - 1;
            } else {
                fileIndex.value = 0;
            }
        }
    }

    return files.value[fileIndex.value];
};

const play = (options = {}) => {
    const file = selectFile(options);

    if (!file) {
        return;
    }

    if (isPaused.value) {
        togglePause();
        return;
    }

    stop();

    const destination = prepare();

    createAudioSource(file, (newSource) => {
        source.value = newSource;
        newSource.connect(destination);

        if (newSource.duration !== null) {
            duration.value = newSource.duration();
        } else if (file.audio !== null) {
            duration.value = parseFloat(file.audio.length);
        }

        newSource.play(file);

        sendMetadata({
            title: file.metadata.title,
            artist: file.metadata.artist
        });
    }, () => {
        stop();

        if (playThrough.value) {
            play({
                isAutoPlay: true
            });
        }
    });
};

const previous = () => {
    if (!isPlaying.value) {
        return;
    }

    play({backward: true});
};

const next = () => {
    if (!isPlaying.value) {
        return;
    }

    play();
};
</script>
