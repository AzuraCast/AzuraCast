<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <div class="flex-fill text-nowrap">
                    <h5 class="card-title">
                        {{ langHeader }}
                    </h5>
                </div>
                <div class="flex-shrink-0 pl-3">
                    <volume-slider v-model.number="trackGain" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="!isPlaying || isPaused"
                        class="btn btn-sm btn-success"
                        @click="play"
                    >
                        <icon icon="play_arrow" />
                    </button>
                    <button
                        v-if="isPlaying && !isPaused"
                        class="btn btn-sm btn-warning"
                        @click="togglePause()"
                    >
                        <icon icon="pause" />
                    </button>
                    <button
                        class="btn btn-sm"
                        @click="previous()"
                    >
                        <icon icon="fast_rewind" />
                    </button>
                    <button
                        class="btn btn-sm"
                        @click="next()"
                    >
                        <icon icon="fast_forward" />
                    </button>
                    <button
                        class="btn btn-sm btn-danger"
                        @click="stop()"
                    >
                        <icon icon="stop" />
                    </button>
                    <button
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
                    <div class="flex-shrink-0 pt-1 pr-2">
                        {{ formatTime(position) }}
                    </div>
                    <div class="flex-fill">
                        <input
                            type="range"
                            min="0"
                            max="100"
                            step="0.1"
                            class="custom-range slider"
                            :value="position"
                        >
                    </div>
                    <div class="flex-shrink-0 pt-1 pl-2">
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
import {computed, ref} from "vue";
import {useWebDjTrack} from "~/components/Public/WebDJ/useWebDjTrack";
import {useTranslate} from "~/vendor/gettext";
import {forEach} from "lodash";

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
    node,
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

const {context, createFileSource, updateMetadata} = node;

const fileIndex = ref(-1);
const files = ref([]);
const duration = ref(0.0);
const loop = ref(false);
const playThrough = ref(false);

const {$gettext} = useTranslate();

const langHeader = computed(() => {
    return isLeftPlaylist.value
        ? this.$gettext('Playlist 1')
        : this.$gettext('Playlist 2');
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

let source = null;

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
    let file = selectFile(options);
    if (!file) {
        return;
    }

    if (isPaused.value) {
        togglePause();
        return;
    }

    stop();

    prepare();

    createFileSource(file, (newSource) => {
        source = newSource;
        source.connect(context.destination);

        if (source.duration !== null) {
            duration.value = source.duration();
        } else if (file.audio !== null) {
            duration.value = parseFloat(this.file.audio.length);
        }

        source.play(file);

        updateMetadata({
            title: this.file.metadata.title,
            artist: this.file.metadata.artist
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
