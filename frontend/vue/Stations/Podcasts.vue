<template>
    <div>
        <episodes-view v-if="activePodcast" v-bind="$props" :podcast="activePodcast" @clear-podcast="onClearPodcast"></episodes-view>
        <list-view v-else v-bind="$props" @select-podcast="onSelectPodcast"></list-view>
    </div>
</template>

<script>
import EpisodesView, { episodeViewProps } from './Podcasts/EpisodesView';
import ListView, { listViewProps } from './Podcasts/ListView';

export default {
    name: 'StationPodcasts',
    components: { ListView, EpisodesView },
    mixins: [episodeViewProps, listViewProps],
    data () {
        return {
            activePodcast: null
        };
    },
    methods: {
        onSelectPodcast (podcast) {
            this.activePodcast = podcast;
        },
        onClearPodcast () {
            this.activePodcast = null;
        }
    }
};
</script>
