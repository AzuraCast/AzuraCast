<template>
    <episodes-view
        v-if="activePodcast"
        v-bind="pickProps(props, episodesViewProps)"
        :podcast="activePodcast"
        :quota-url="quotaUrl"
        @clear-podcast="onClearPodcast"
    />
    <list-view
        v-else
        v-bind="pickProps(props, listViewProps)"
        :quota-url="quotaUrl"
        @select-podcast="onSelectPodcast"
    />
</template>

<script setup>
import EpisodesView from './Podcasts/EpisodesView';
import ListView from './Podcasts/ListView';
import {ref} from "vue";
import listViewProps from "./Podcasts/listViewProps";
import {pickProps} from "~/functions/pickProps";
import {getStationApiUrl} from "~/router";

const props = defineProps({
    ...listViewProps
});

const quotaUrl = getStationApiUrl('/quota/station_podcasts');

const activePodcast = ref(null);

const onSelectPodcast = (podcast) => {
    activePodcast.value = podcast;
};

const onClearPodcast = () => {
    activePodcast.value = null;
}
</script>
