<template>
    <episodes-view
        v-if="activePodcast"
        v-bind="pickProps(props, episodesViewProps)"
        :podcast="activePodcast"
        @clear-podcast="onClearPodcast"
    />
    <list-view
        v-else
        v-bind="pickProps(props, listViewProps)"
        @select-podcast="onSelectPodcast"
    />

    <lightbox ref="$lightbox" />
</template>

<script setup>
import EpisodesView from './Podcasts/EpisodesView';
import ListView from './Podcasts/ListView';
import {ref} from "vue";
import episodesViewProps from "./Podcasts/episodesViewProps";
import listViewProps from "./Podcasts/listViewProps";
import {pickProps} from "~/functions/pickProps";
import Lightbox from "~/components/Common/Lightbox.vue";
import {useProvideLightbox} from "~/vendor/lightbox";

const props = defineProps({
    ...episodesViewProps,
    ...listViewProps
});

const activePodcast = ref(null);

const onSelectPodcast = (podcast) => {
    activePodcast.value = podcast;
};

const onClearPodcast = () => {
    activePodcast.value = null;
}

const $lightbox = ref(); // Template Ref
useProvideLightbox($lightbox);
</script>
