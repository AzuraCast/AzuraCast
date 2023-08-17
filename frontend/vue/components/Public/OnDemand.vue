<template>
    <section
        id="content"
        role="main"
        class="d-flex align-items-stretch"
        style="height: 100vh;"
    >
        <div
            class="container pt-5 pb-5 h-100"
            style="flex: 1;"
        >
            <div
                class="card"
                style="height: 100%;"
            >
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
                                <icon icon="cloud_download" />
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

<script setup>
import InlinePlayer from '../InlinePlayer';
import DataTable from '~/components/Common/DataTable';
import {forEach} from 'lodash';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import {useTranslate} from "~/vendor/gettext";
import formatFileSize from "../../functions/formatFileSize";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import {ref} from "vue";
import {useProvideLightbox} from "~/vendor/lightbox";

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

const fields = [
    {key: 'download_url', label: ' '},
    {key: 'art', label: $gettext('Art')},
    {
        key: 'title',
        label: $gettext('Title'),
        sortable: true,
        selectable: true,
        formatter: (value, key, item) => item.media.title,
    },
    {
        key: 'artist',
        label: $gettext('Artist'),
        sortable: true,
        selectable: true,
        formatter: (value, key, item) => item.media.artist,
    },
    {
        key: 'album',
        label: $gettext('Album'),
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (value, key, item) => item.media.album
    }
];

forEach(props.customFields.slice(), (field) => {
    fields.push({
        key: field.display_key,
        label: field.label,
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (value, key, item) => item.media.custom_fields[field.key]
    });
});

const $lightbox = ref(); // Template Ref
useProvideLightbox($lightbox);
</script>

<style lang="scss">
.ondemand.embed {
    .container {
        max-width: 100%;
        padding: 0 !important;
    }
}

#public_on_demand {
    .datatable-main {
        overflow-y: auto;
    }

    table.table {
        thead tr th:nth-child(1),
        tbody tr td:nth-child(1) {
            padding-right: 0.75rem;
            width: 3rem;
            white-space: nowrap;
        }

        thead tr th:nth-child(2),
        tbody tr td:nth-child(2) {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            width: 40px;
        }

        thead tr th:nth-child(3),
        tbody tr td:nth-child(3) {
            padding-left: 0.5rem;
        }
    }
}
</style>
