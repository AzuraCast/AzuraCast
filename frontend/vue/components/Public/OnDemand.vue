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
                <div class="card-header bg-primary-dark">
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
                        <div class="flex-fill text-right">
                            <inline-player ref="player" />
                        </div>
                    </div>
                </div>

                <data-table
                    id="station_on_demand_table"
                    ref="datatable"
                    paginated
                    select-fields
                    :fields="fields"
                    :api-url="listUrl"
                >
                    <template #cell(download_url)="row">
                        <play-button
                            class="file-icon"
                            icon-class="outlined"
                            :url="row.item.download_url"
                            :is-stream="false"
                        />
                        <template v-if="showDownloadButton">
                            &nbsp;
                            <a
                                class="name"
                                :href="row.item.download_url"
                                target="_blank"
                                :title="$gettext('Download')"
                            >
                                <icon icon="cloud_download" />
                            </a>
                        </template>
                    </template>
                    <template #cell(media_art)="row">
                        <a
                            :href="row.item.media_art"
                            class="album-art"
                            target="_blank"
                            data-fancybox="gallery"
                        >
                            <img
                                class="media_manager_album_art"
                                :alt="$gettext('Album Art')"
                                :src="row.item.media_art"
                            >
                        </a>
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
</template>

<script setup>
import InlinePlayer from '../InlinePlayer';
import DataTable from '~/components/Common/DataTable';
import {forEach} from 'lodash';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import {useTranslate} from "~/vendor/gettext";

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
        default: []
    },
    showDownloadButton: {
        type: Boolean,
        default: false
    }
});

const {$gettext} = useTranslate();

let fields = [
    {key: 'download_url', label: ' '},
    {key: 'media_art', label: $gettext('Art')},
    {key: 'media_title', label: $gettext('Title'), sortable: true, selectable: true},
    {key: 'media_artist', label: $gettext('Artist'), sortable: true, selectable: true},
    {key: 'media_album', label: $gettext('Album'), sortable: true, selectable: true, visible: false},
    {key: 'playlist', label: $gettext('Playlist'), sortable: true, selectable: true, visible: false}
];

forEach(props.customFields.slice(), (field) => {
    fields.push({
        key: field.display_key,
        label: field.label,
        sortable: true,
        selectable: true,
        visible: false
    });
});
</script>

<style lang="scss">
.ondemand.embed {
    .container {
        max-width: 100%;
        padding: 0 !important;
    }
}

#station_on_demand_table {
    .datatable-main {
        overflow-y: auto;
    }

    table.b-table {
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

    img.media_manager_album_art {
        width: 40px;
        height: auto;
        border-radius: 5px;
    }
}
</style>
