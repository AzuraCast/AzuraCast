<template>
    <section id="content" role="main" class="d-flex align-items-stretch" style="height: 100vh;">
        <div class="container pt-5 pb-5 h-100" style="flex: 1;">
            <div class="card" style="height: 100%;">
                <div class="card-header bg-primary-dark">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink">
                            <h2 class="card-title py-2">
                                <template v-if="stationName">
                                    {{ stationName }}
                                </template>
                                <template v-else>
                                    <translate key="lang_title">On-Demand Media</translate>
                                </template>
                            </h2>
                        </div>
                        <div class="flex-fill text-right">
                            <inline-player ref="player"></inline-player>
                        </div>
                    </div>
                </div>

                <data-table ref="datatable" id="station_on_demand_table" paginated select-fields
                            :fields="fields" :api-url="listUrl">
                    <template #cell(download_url)="row">
                        <play-button class="file-icon" icon-class="outlined" :url="row.item.download_url"
                                     :is-stream="false"></play-button>
                        <template v-if="showDownloadButton">
                            &nbsp;
                            <a class="name" :href="row.item.download_url" target="_blank" :title="langDownload">
                                <icon icon="cloud_download"></icon>
                            </a>
                        </template>
                    </template>
                    <template #cell(media_art)="row">
                        <a :href="row.item.media_art" class="album-art" target="_blank"
                           data-fancybox="gallery">
                            <img class="media_manager_album_art" :alt="langAlbumArt" :src="row.item.media_art">
                        </a>
                    </template>
                    <template #cell(size)="row">
                        <template v-if="!row.item.size">&nbsp;</template>
                        <template v-else>
                            {{ formatFileSize(row.item.size) }}
                        </template>
                    </template>
                </data-table>
            </div>
        </div>
    </section>
</template>

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

<script>
import InlinePlayer from '../InlinePlayer';
import DataTable from '~/components/Common/DataTable';
import _ from 'lodash';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";

export default {
    components: {PlayButton, Icon, DataTable, InlinePlayer},
    props: {
        listUrl: String,
        stationName: String,
        customFields: Array,
        showDownloadButton: Boolean
    },
    data() {
        let fields = [
            {key: 'download_url', label: ' '},
            {key: 'media_art', label: this.$gettext('Art')},
            {key: 'media_title', label: this.$gettext('Title'), sortable: true, selectable: true},
            {key: 'media_artist', label: this.$gettext('Artist'), sortable: true, selectable: true},
            {key: 'media_album', label: this.$gettext('Album'), sortable: true, selectable: true, visible: false},
            {key: 'playlist', label: this.$gettext('Playlist'), sortable: true, selectable: true, visible: false}
        ];

        _.forEach(this.customFields.slice(), (field) => {
            fields.push({
                key: field.display_key,
                label: field.label,
                sortable: true,
                selectable: true,
                visible: false
            });
        });

        return {
            fields: fields
        };
    },
    computed: {
        langAlbumArt () {
            return this.$gettext('Album Art');
        },
        langPlayPause () {
            return this.$gettext('Play/Pause');
        },
        langDownload () {
            return this.$gettext('Download');
        }
    }
};
</script>
