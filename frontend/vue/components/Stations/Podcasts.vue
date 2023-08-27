<template>
    <episodes-view
        v-if="activePodcast"
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

<script setup lang="ts">
import EpisodesView from './Podcasts/EpisodesView.vue';
import ListView from './Podcasts/ListView.vue';
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
