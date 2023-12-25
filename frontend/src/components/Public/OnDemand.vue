<template>
    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
        <div class="container">
            <div class="card">
                <div class="card-header text-bg-primary">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink">
                            <h2 class="card-title py-2">
                                <template v-if="stationName">
                                    {{ stationName }}
                                </template>
                                <template v-else>
                                    {{ $gettext('On-Demand Media') }}
                                </template>
                            </h2>
                        </div>
                        <div class="flex-fill text-end">
                            <inline-player ref="player" />
                        </div>
                    </div>
                </div>

                <data-table
                    id="public_on_demand"
                    ref="datatable"
                    paginated
                    select-fields
                    :fields="fields"
                    :api-url="listUrl"
                >
                    <template #cell(download_url)="row">
                        <play-button
                            class="btn-lg"
                            :url="row.item.download_url"
                        />
                        <template v-if="showDownloadButton">
                            <a
                                class="name btn btn-lg p-0 ms-2"
                                :href="row.item.download_url"
                                target="_blank"
                                :title="$gettext('Download')"
                            >
                                <icon :icon="IconDownload" />
                            </a>
                        </template>
                    </template>
                    <template #cell(art)="row">
                        <album-art :src="row.item.media.art" />
                    </template>
                    <template #cell(size)="row">
                        <template v-if="!row.item.size">
&nbsp;
                        </template>
                        <template v-else>
                            {{ formatFileSize(row.item.size) }}
                        </template>
                    </template>
                </data-table>
            </div>
        </div>
    </section>

    <lightbox ref="$lightbox" />
</template>

<script setup lang="ts">
import InlinePlayer from '../InlinePlayer.vue';
import DataTable, {DataTableField} from '~/components/Common/DataTable.vue';
import {forEach} from 'lodash';
import Icon from '~/components/Common/Icon.vue';
import PlayButton from "~/components/Common/PlayButton.vue";
import {useTranslate} from "~/vendor/gettext";
import formatFileSize from "../../functions/formatFileSize";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import {ref} from "vue";
import {LightboxTemplateRef, useProvideLightbox} from "~/vendor/lightbox";
import {IconDownload} from "~/components/Common/icons";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    stationName: {
        type: String,
        required: true
    },
    customFields: {
        type: Array,
        default: () => {
            return [];
        }
    },
    showDownloadButton: {
        type: Boolean,
        default: false
    }
});

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'download_url', label: ' ', class: 'shrink'},
    {key: 'art', label: $gettext('Art'), class: 'shrink'},
    {
        key: 'title',
        label: $gettext('Title'),
        sortable: true,
        selectable: true,
        formatter: (_value, _key, item) => item.media.title,
    },
    {
        key: 'artist',
        label: $gettext('Artist'),
        sortable: true,
        selectable: true,
        formatter: (_value, _key, item) => item.media.artist,
    },
    {
        key: 'album',
        label: $gettext('Album'),
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (_value, _key, item) => item.media.album
    }
];

forEach(props.customFields.slice(), (field) => {
    fields.push({
        key: field.display_key,
        label: field.label,
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (_value, _key, item) => item.media.custom_fields[field.key]
    });
});

const $lightbox = ref<LightboxTemplateRef>(null);
useProvideLightbox($lightbox);
</script>
