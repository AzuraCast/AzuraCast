<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    <translate key="lang_title">Song Listener Impact</translate>
                </h2>
                <div class="flex-shrink">
                    <a class="btn btn-bg" id="btn-export" :href="exportUrl" target="_blank">
                        <icon icon="file_download"></icon>
                        <translate key="lang_download_csv_button">Download CSV</translate>
                    </a>
                </div>
            </div>
        </div>

        <data-table ref="datatable" responsive paginated handle-client-side select-fields :fields="fields"
                    :apiUrl="apiUrl">
            <template #cell(playlists)="row">
                {{ row.item.playlists.join(', ') }}
            </template>
            <template #cell(delta_positive)="row">
                <span class="text-success">
                    {{ row.item.delta_positive }}
                </span>
            </template>
            <template #cell(delta_positive)="row">
                <span class="text-success">
                    {{ row.item.delta_positive }}
                </span>
            </template>
            <template #cell(delta_negative)="row">
                <span class="text-danger">
                    {{ row.item.delta_negative }}
                </span>
            </template>
        </data-table>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";

export default {
    name: 'StationsReportsPerformance',
    components: {Icon, DataTable},
    props: {
        apiUrl: String
    },
    data() {
        return {
            fields: [
                {key: 'title', label: this.$gettext('Title'), sortable: true},
                {key: 'artist', label: this.$gettext('Artist'), sortable: true},
                {key: 'path', label: this.$gettext('File Name'), sortable: false},
                {key: 'length_raw', label: this.$gettext('Length'), selectable: true, sortable: false},
                {key: 'length', label: this.$gettext('Length Text'), visible: false, selectable: true, sortable: false},
                {key: 'playlists', label: this.$gettext('Playlist(s)'), selectable: true, sortable: false},
                {key: 'delta_positive', label: 'Δ ' + this.$gettext('Joins'), selectable: true, sortable: true},
                {key: 'delta_negative', label: 'Δ ' + this.$gettext('Losses'), selectable: true, sortable: true},
                {key: 'delta_total', label: 'Δ ' + this.$gettext('Total'), selectable: true, sortable: true},
                {key: 'num_plays', label: this.$gettext('Num Plays'), selectable: true, sortable: true},
                {key: 'percent_plays', label: this.$gettext('Play %'), selectable: true, sortable: false},
                {key: 'ratio', label: this.$gettext('Ratio'), selectable: true, sortable: false},
            ],
        }
    },
    computed: {
        exportUrl() {
            return this.apiUrl + '?format=csv';
        }
    }
};
</script>
