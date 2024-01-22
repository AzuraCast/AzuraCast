<template>
    <loading
        :loading="isLoading"
        lazy
    >
        <div class="card-body">
            <div class="d-flex">
                <div class="flex-fill">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <router-link :to="{name: 'public:podcasts'}">
                                    {{ $gettext('Podcasts') }}
                                </router-link>
                            </li>
                            <li class="breadcrumb-item">
                                {{ podcast.title }}
                            </li>
                        </ol>
                    </nav>

                    <h4 class="card-title mb-1">
                        {{ podcast.title }}
                        <br>
                        <small>
                            {{ $gettext('by') }} <a
                                :href="'mailto:'+podcast.email"
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
                    <div class="buttons">
                        <a
                            class="btn btn-warning btn-sm"
                            :href="podcast.links.public_feed"
                            target="_blank"
                        >
                            <icon :icon="IconRss" />
                            {{ $gettext('RSS') }}
                        </a>
                    </div>
                </div>
                <div class="flex-shrink ps-3">
                    <album-art
                        :src="podcast.art"
                        :width="128"
                    />
                </div>
            </div>
        </div>
        <data-table
            id="podcast-episodes"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="episodesUrl"
        >
            <template #cell(play_button)="{item}">
                <play-button
                    icon-class="lg"
                    :url="item.links.download"
                />
            </template>
            <template #cell(art)="{item}">
                <album-art
                    :src="item.art"
                    :width="64"
                />
            </template>
            <template #cell(title)="{item}">
                <h5 class="m-0">
                    <router-link
                        :to="{name: 'public:podcast:episode', params: {podcast_id: podcast.id, episode_id: item.id}}"
                    >
                        {{ item.title }}
                    </router-link>
                </h5>
                <div class="badges my-2">
                    <span
                        v-if="item.publish_at"
                        class="badge text-bg-secondary"
                    >
                        {{ formatTimestampAsDateTime(item.publish_at) }}
                    </span>
                    <span
                        v-else
                        class="badge text-bg-secondary"
                    >
                        {{ formatTimestampAsDateTime(item.created_at) }}
                    </span>
                    <span
                        v-if="item.explicit"
                        class="badge text-bg-danger"
                    >
                        {{ $gettext('Explicit') }}
                    </span>
                </div>
                <p class="card-text">
                    {{ item.description_short }}
                </p>
            </template>
            <template #cell(actions)="{item}">
                <div class="btn-group btn-group-sm">
                    <router-link
                        :to="{name: 'public:podcast:episode', params: {podcast_id: podcast.id, episode_id: item.id}}"
                        class="btn btn-primary"
                    >
                        {{ $gettext('Details') }}
                    </router-link>
                </div>
            </template>
        </data-table>
    </loading>
</template>

<script setup lang="ts">
import {getStationApiUrl} from "~/router.ts";
import {useRoute} from "vue-router";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {IconRss} from "~/components/Common/icons.ts";
import Icon from "~/components/Common/Icon.vue";
import PlayButton from "~/components/Common/PlayButton.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";

const {params} = useRoute();

const podcastUrl = getStationApiUrl(`/podcast/${params.podcast_id}`);

const {axios} = useAxios();
const {state: podcast, isLoading} = useRefreshableAsyncState(
    () => axios.get(podcastUrl.value).then((r) => r.data),
    {},
);

const episodesUrl = getStationApiUrl(`/podcast/${params.podcast_id}/episodes`);

const {$gettext} = useTranslate();
const fields: DataTableField[] = [
    {key: 'play_button', label: '', sortable: false, class: 'shrink pe-0'},
    {key: 'art', label: '', sortable: false, class: 'shrink pe-0'},
    {key: 'title', label: $gettext('Episode'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const {formatTimestampAsDateTime} = useStationDateTimeFormatter();
</script>
