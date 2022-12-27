<template>
    <episodes-view v-if="activePodcast" v-bind="$props" :podcast="activePodcast"
                   @clear-podcast="onClearPodcast"></episodes-view>
    <list-view v-else v-bind="$props" @select-podcast="onSelectPodcast"></list-view>
</template>

<script setup>
import EpisodesView from './Podcasts/EpisodesView';
import ListView from './Podcasts/ListView';
import {ref} from "vue";
import episodesViewProps from "./Podcasts/episodesViewProps";
import listViewProps from "./Podcasts/listViewProps";

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
</script>
