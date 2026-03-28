<template>
    <data-table
        v-if="groupLayout === 'table'"
        id="podcasts"
        paginated
        :fields="fields"
        :provider="podcastsItemProvider"
    >
        <template #cell(art)="{item}">
            <album-art
                :src="item.art"
                :width="96"
            />
        </template>
        <template #cell(title)="{item}">
            <h5 class="m-0">
                <router-link
                    :to="{name: 'public:podcast', params: {podcast_id: item.id}}"
                >
                    {{ item.title }}
                </router-link>
                <br>
                <small>
                    {{ $gettext('by') }} <a
                        :href="'mailto:'+item.email"
                        target="_blank"
                    >{{ item.author }}</a>
                </small>
            </h5>
            <div class="badges my-2">
                <span class="badge text-bg-info">
                    {{ item.language_name }}
                </span>
                <span
                    v-for="category in item.categories"
                    :key="category.category"
                    class="badge text-bg-secondary"
                >
                    {{ category.text }}
                </span>
            </div>
            <p class="card-text">
                {{ item.description_short }}
            </p>
        </template>
        <template #cell(actions)="{item}">
            <div class="btn-group btn-group-sm">
                <router-link
                    :to="{name: 'public:podcast', params: {podcast_id: item.id}}"
                    class="btn btn-primary"
                >
                    {{ $gettext('Episodes') }}
                </router-link>

                <a
                    class="btn btn-warning"
                    :href="item.links.public_feed"
                    target="_blank"
                >
                    <icon-bi-rss-fill/>

                    {{ $gettext('RSS') }}
                </a>
            </div>
        </template>
    </data-table>
    <grid-layout
        v-else
        id="podcasts_grid"
        :provider="podcastsItemProvider"
        paginated
    >
        <template #item="{item}">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title m-0 flex-fill">
                        <router-link
                            :to="{name: 'public:podcast', params: {podcast_id: item.id}}"
                        >
                            {{ item.title }}
                        </router-link>
                        <br>
                        <small>
                            {{ $gettext('by') }} <a
                                :href="'mailto:'+item.email"
                                target="_blank"
                            >{{ item.author }}</a>
                        </small>
                    </h5>
                    <div class="flex-shrink-0 ps-2">
                        <album-art
                            :src="item.art"
                            :width="64"
                        />
                    </div>
                </div>
                <div class="card-body">
                    <div class="badges my-2">
                        <span class="badge text-bg-info">
                            {{ item.language_name }}
                        </span>
                        <span
                            v-for="category in item.categories"
                            :key="category.category"
                            class="badge text-bg-secondary"
                        >
                            {{ category.text }}
                        </span>
                    </div>
                    <p class="card-text">
                        {{ item.description_short }}
                    </p>
                </div>
            </div>
        </template>
    </grid-layout>
</template>

<script setup lang="ts">
import AlbumArt from "~/components/Common/AlbumArt.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import GridLayout from "~/components/Common/GridLayout.vue";
import {usePodcastGlobals} from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {ApiPodcastRow} from "~/components/Public/Podcasts/usePodcastQuery.ts";
import IconBiRssFill from "~icons/bi/rss-fill";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {groupLayout, stationId} = usePodcastGlobals();

const {getStationApiUrl} = useApiRouter();
const apiUrl = getStationApiUrl('/public/podcasts', stationId);

const {$gettext} = useTranslate();

const fields: DataTableField<ApiPodcastRow>[] = [
    {key: 'art', label: '', sortable: false, class: 'shrink pe-0'},
    {key: 'title', label: $gettext('Podcast'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const podcastsItemProvider = useApiItemProvider<ApiPodcastRow>(
    apiUrl,
    [
        QueryKeys.PublicPodcasts,
        {station: stationId},
    ],
    {
        staleTime: 5 * 60 * 1000
    }
)
</script>
