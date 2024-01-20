<template>
    <div class="card-body">
        <loading
            :loading="podcastLoading || episodeLoading"
            lazy
        >
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
        </loading>
    </div>

    <div
        class="card-body alert alert-secondary"
        aria-live="polite"
    >
        <loading
            :loading="podcastLoading"
            lazy
        >
            <h4 class="card-title mb-1">
                {{ podcast.title }}
                <br>
                <small>
                    {{ $gettext('by') }} <a
                        :href="'mailto:'+podcast.email"
                        class="alert-link"
                        target="_blank"
                    >{{ podcast.author }}</a>
                </small>
            </h4>

            <div class="badges my-2">
                <span class="badge text-bg-info">
                    {{ podcast.language_name }}
                </span>
                <span
                    v-for="category in podcast.categories"
                    :key="category.category"
                    class="badge text-bg-secondary"
                >
                    {{ category.text }}
                </span>
            </div>
            <p class="card-text">
                {{ podcast.description }}
            </p>
        </loading>
    </div>

    <div class="card-body">
        <loading
            :loading="episodeLoading"
            lazy
        >
            <div class="d-flex">
                <div class="flex-shrink-0 pe-3">
                    <play-button
                        icon-class="lg"
                        :url="episode.links.download"
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
                            {{ formatTime(episode.publish_at) }}
                        </span>
                        <span
                            v-else
                            class="badge text-bg-secondary"
                        >
                            {{ formatTime(episode.created_at) }}
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
                        :src="episode.art"
                        :width="96"
                    />
                </div>
            </div>
        </loading>
    </div>
</template>

<script setup lang="ts">
import Loading from "~/components/Common/Loading.vue";
import {useRoute} from "vue-router";
import {getStationApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import PlayButton from "~/components/Common/PlayButton.vue";
import {useLuxon} from "~/vendor/luxon.ts";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast.ts";

const {params} = useRoute();

const podcastUrl = getStationApiUrl(`/podcast/${params.podcast_id}`);
const episodeUrl = getStationApiUrl(`/podcast/${params.podcast_id}/episode/${params.episode_id}`);

const {axios} = useAxios();

const {state: podcast, isLoading: podcastLoading} = useRefreshableAsyncState(
    () => axios.get(podcastUrl.value).then((r) => r.data),
    {},
);

const {state: episode, isLoading: episodeLoading} = useRefreshableAsyncState(
    () => axios.get(episodeUrl.value).then((r) => r.data),
    {},
);

const {DateTime} = useLuxon();
const {timezone} = useAzuraCastStation();
const {timeConfig} = useAzuraCast();

const formatTime = (value) => {
    if (!value) {
        return '';
    }

    return DateTime.fromSeconds(value).setZone(timezone).toLocaleString(
        {...DateTime.DATETIME_MED, ...timeConfig}
    );
};
</script>
