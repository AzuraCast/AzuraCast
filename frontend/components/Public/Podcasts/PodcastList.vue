<template>
    <data-table
        v-if="groupLayout === 'table'"
        id="podcasts"
        ref="$datatable"
        paginated
        :fields="fields"
        :api-url="apiUrl"
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
                    <icon :icon="IconRss" />
                    {{ $gettext('RSS') }}
                </a>
            </div>
        </template>
    </data-table>
    <grid-layout
        v-else
        id="podcasts_grid"
        ref="$grid"
        paginated
        :api-url="apiUrl"
    >
        <template #item="{item}: {item: ApiPodcast}">
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
import {getStationApiUrl} from "~/router.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {IconRss} from "~/components/Common/icons.ts";
import Icon from "~/components/Common/Icon.vue";
import GridLayout from "~/components/Common/GridLayout.vue";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";
import {usePodcastGlobals} from "~/components/Public/Podcasts/usePodcastGlobals.ts";

const {groupLayout, stationId} = usePodcastGlobals();

const apiUrl = getStationApiUrl('/public/podcasts', stationId);

const {$gettext} = useTranslate();

const fields: DataTableField<ApiPodcast>[] = [
    {key: 'art', label: '', sortable: false, class: 'shrink pe-0'},
    {key: 'title', label: $gettext('Podcast'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];
</script>
