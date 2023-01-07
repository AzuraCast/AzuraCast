<template>
    <div style="overflow-x: hidden">
        <data-table
            id="song_requests"
            ref="datatable"
            paginated
            select-fields
            :page-options="pageOptions"
            :fields="fields"
            :responsive="false"
            :api-url="requestListUri"
        >
            <template #cell(name)="row">
                <div class="d-flex align-items-center">
                    <album-art
                        v-if="showAlbumArt"
                        :src="row.item.song.art"
                        :width="40"
                        class="flex-shrink-1 pr-3"
                    />
                    <div class="flex-fill">
                        {{ row.item.song.title }}<br>
                        <small>{{ row.item.song.artist }}</small>
                    </div>
                </div>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doSubmitRequest(row.item.request_url)"
                    >
                        {{ $gettext('Request') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import {forEach} from 'lodash';
import AlbumArt from '~/components/Common/AlbumArt';

/* TODO Options API */

export default {
    components: {AlbumArt, DataTable},
    props: {
        requestListUri: {
            type: String,
            required: true
        },
        showAlbumArt: {
            type: Boolean,
            default: true
        },
        customFields: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    emits: ['submitted'],
    data () {
        let fields = [
            {key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: true, selectable: true},
            {
                key: 'song.title',
                label: this.$gettext('Title'),
                sortable: true,
                selectable: true,
                visible: false,
            },
            {
                key: 'song.artist',
                label: this.$gettext('Artist'),
                sortable: true,
                selectable: true,
                visible: false,
            },
            {
                key: 'song.album',
                label: this.$gettext('Album'),
                sortable: true,
                selectable: true,
                visible: false
            },
            {
                key: 'song.genre',
                label: this.$gettext('Genre'),
                sortable: true,
                selectable: true,
                visible: false
            }
        ];

        forEach(this.customFields.slice(), (field) => {
            fields.push({
                key: 'song.custom_fields.' + field.short_name,
                label: field.name,
                sortable: false,
                selectable: true,
                visible: false
            });
        });

        fields.push(
            {key: 'actions', label: this.$gettext('Actions'), class: 'shrink', sortable: false}
        );

        return {
            fields: fields,
            pageOptions: [
                10, 25
            ]
        };
    },
    methods: {
        doSubmitRequest (url) {
            this.axios.post(url).then((resp) => {
                this.$notifySuccess(resp.data.message);
                this.$emit('submitted');
            }).catch(() => {
                this.$emit('submitted');
            });
        }
    }
};
</script>

<style lang="scss">
img.album_art {
    width: 40px;
    height: auto;
    border-radius: 5px;
}
</style>
