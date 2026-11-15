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
                    <volume-slider v-model.number="gain"/>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="!isPlaying"
                        type="button"
                        class="btn btn-sm btn-success"
                        @click="togglePause()"
                        :title="$gettext('Play')"
                        :aria-label="$gettext('Play')"
                    >
                        <icon-ic-play-circle/>
                    </button>
                    <button
                        v-if="isPlaying"
                        type="button"
                        class="btn btn-sm btn-warning"
                        @click="togglePause()"
                        :title="$gettext('Pause')"
                        :aria-label="$gettext('Pause')"
                    >
                        <icon-ic-pause-circle/>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        @click="previous()"
                        :title="$gettext('Previous Track')"
                        :aria-label="$gettext('Previous Track')"
                    >
                        <icon-ic-fast-rewind/>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        @click="next()"
                        :title="$gettext('Next Track')"
                        :aria-label="$gettext('Next Track')"
                    >
                        <icon-ic-fast-forward/>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="stop()"
                        :title="$gettext('Stop')"
                        :aria-label="$gettext('Stop')"
                    >
                        <icon-ic-stop/>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="{ 'btn-primary': isCued }"
                        @click="toggleCue"
                    >
                        {{ $gettext('Cue') }}
                    </button>
                </div>
            </div>

            <div
                v-if="isLoaded"
                class="my-3"
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
                            class="form-range w-100 slider"
                            :disabled="!canSeek"
                        >
                    </div>
                    <div class="flex-shrink-0 pt-1 ps-2">
                        {{ formatTime(duration) }}
                    </div>
                </div>

                <meter class="level w-100" min="-60" max="0" low="-18" high="-6" optimum="-60"
                       :value="leftLevel"/>
            </div>

            <div class="form-group my-2">
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

            <div class="form-group mt-2">
                <div class="custom-file">
                    <label
                        :for="id + '_files'"
                        class="form-label"
                    >
                        {{ $gettext('Add Files to Playlist') }}
                    </label>
                    <input
                        :id="id + '_files'"
                        type="file"
                        class="form-control form-control-sm files"
                        accept="audio/*"
                        multiple
                        @change="onFileSelected"
                    >
                </div>
            </div>
        </div>

        <div
            v-if="files.length > 0"
            class="list-group list-group-flush"
        >
            <a
                v-for="rowFile in files"
                :key="rowFile.index"
                href="#"
                class="list-group-item list-group-item-action flex-column align-items-start"
                :class="{ active: rowFile.isCurrent }"
                @click.prevent="playFile(rowFile.index)"
            >
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-0">{{
                            rowFile.title || $gettext('Unknown Title')
                    }}</h5>
                    <small class="pt-1">{{ rowFile.duration }}</small>
                </div>
                <p class="mb-0">{{ rowFile.artist || $gettext('Unknown Artist') }}</p>
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { formatTime, Side } from "@webcast.js/webcaster";
import { computed } from "vue";
import {
    useInjectWebcasterDeck,
    useProvideWebcasterDeck,
} from "~/components/Public/WebDJ/useWebcasterDeck.ts";
import VolumeSlider from "~/components/Public/WebDJ/VolumeSlider.vue";
import { useTranslate } from "~/vendor/gettext";
import IconIcFastForward from "~icons/ic/baseline-fast-forward";
import IconIcFastRewind from "~icons/ic/baseline-fast-rewind";
import IconIcPauseCircle from "~icons/ic/baseline-pause-circle";
import IconIcPlayCircle from "~icons/ic/baseline-play-circle";
import IconIcStop from "~icons/ic/baseline-stop";

const props = defineProps<{
    side: Side;
}>();

const id = computed(() => {
    return props.side === "left" ? "playlist_1" : "playlist_2";
});

const isLeftPlaylist = computed(() => {
    return props.side === "left";
});

useProvideWebcasterDeck(props.side);

const {
    gain,
    leftLevel,
    position,
    duration,
    playThrough,
    loop,
    toggleCue,
    isCued,
    isLoaded,
    isPlaying,
    togglePause,
    previous,
    next,
    stop,
    seek,
    addFiles,
    files,
    playFile,
} = useInjectWebcasterDeck();

const canSeek = computed(() => {
    return Number.isFinite(duration.value) && duration.value > 0;
});

const seekingPosition = computed({
    get: () => {
        return canSeek.value
            ? 100.0 * (position.value / duration.value)
            : 0;
    },
    set: (val) => {
        if (!canSeek.value) {
            return;
        }

        seek((val / 100) * duration.value);
    },
});

const { $gettext } = useTranslate();

const langHeader = computed(() => {
    return isLeftPlaylist.value
        ? $gettext("Playlist 1")
        : $gettext("Playlist 2");
});

const onFileSelected = (e: Event) => {
    const eventTarget = e.target as HTMLInputElement;
    if (eventTarget.files) {
        addFiles(eventTarget.files);
    }

    eventTarget.value = "";
};
</script>
