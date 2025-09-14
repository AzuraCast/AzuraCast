<template>
    <div class="card-body">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item">
                    <router-link :to="{name: 'public:podcasts'}">
                        {{ $gettext('Podcasts') }}
                    </router-link>
                </li>
                <li class="breadcrumb-item">
                    <router-link :to="{name: 'public:podcast', params: {podcast_id: podcast.id}}">
                        {{ podcast.title }}
                    </router-link>
                </li>
                <li class="breadcrumb-item">
                    {{ episode.title }}
                </li>
            </ol>
        </nav>
    </div>

    <div
        class="card-body alert alert-secondary"
        aria-live="polite"
    >
        <podcast-common :podcast="podcast"/>
    </div>

    <div class="card-body">
        <div class="d-flex">
            <div class="flex-shrink-0 pe-3">
                <play-button
                    icon-class="xl"
                    :stream="{
                        title: episode.title,
                        url: episode.links.download
                    }"
                />
            </div>
            <div class="flex-fill">
                <h4 class="card-title mb-1">
                    {{ episode.title }}
                </h4>

                <div class="badges my-2">
                    <span
                        v-if="episode.publish_at"
                        class="badge text-bg-secondary"
                    >
                        {{ formatTimestampAsDateTime(episode.publish_at) }}
                    </span>
                    <span
                        v-else
                        class="badge text-bg-secondary"
                    >
                        {{ formatTimestampAsDateTime(episode.created_at) }}
                    </span>
                    <span
                        v-if="episode.explicit"
                        class="badge text-bg-danger"
                    >
                        {{ $gettext('Explicit') }}
                    </span>
                </div>

                <p class="card-text">
                    {{ episode.description }}
                </p>
            </div>
            <div class="flex-shrink-0 ps-3">
                <album-art
                    v-if="episode.art !== null"
                    :src="episode.art"
                    :width="96"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AlbumArt from "~/components/Common/AlbumArt.vue";
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import PodcastCommon from "~/components/Public/Podcasts/PodcastCommon.vue";
import {usePodcastGlobals} from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import {ApiPodcastRow} from "~/components/Public/Podcasts/usePodcastQuery.ts";
import {PodcastEpisodeRow} from "~/components/Public/Podcasts/PodcastEpisodeWrapper.vue";

defineProps<{
    podcast: ApiPodcastRow,
    episode: PodcastEpisodeRow
}>();

const {stationTz} = usePodcastGlobals();

const {formatTimestampAsDateTime} = useStationDateTimeFormatter(stationTz);
</script>
