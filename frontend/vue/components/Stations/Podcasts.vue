<template>
    <episodes-view v-if="activePodcast" v-bind="$props" :podcast="activePodcast"
                   @clear-podcast="onClearPodcast"></episodes-view>
    <list-view v-else v-bind="$props" @select-podcast="onSelectPodcast"></list-view>
</template>

<script setup>
import EpisodesView, {episodeViewProps} from './Podcasts/EpisodesView';
import ListView, {listViewProps} from './Podcasts/ListView';
import {ref} from "vue";

const props = defineProps({
    ...episodeViewProps.props,
    ...listViewProps.props
});

const activePodcast = ref(null);

const onSelectPodcast = (podcast) => {
    activePodcast.value = podcast;
};

const onClearPodcast = () => {
    activePodcast.value = null;
}
</script>
