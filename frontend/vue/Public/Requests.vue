<template>
    <data-table ref="datatable" id="song_requests" paginated select-fields :page-options="pageOptions" :fields="fields"
                :responsive="false" :api-url="requestListUri">
        <template v-slot:cell(name)="row">
            <album-art :src="row.item.song_art" :width="40" class="float-left pr-3"></album-art>
            {{ row.item.song_title }}<br>
            <small>{{ row.item.song_artist }}</small>
        </template>
        <template v-slot:cell(actions)="row">
            <b-button-group size="sm">
                <b-button size="sm" variant="primary" @click.prevent="doSubmitRequest(row.item.request_url)">
                    <translate key="lang_btn_request">Request</translate>
                </b-button>
            </b-button-group>
        </template>
    </data-table>
</template>

<style lang="scss">
img.album_art {
    width: 40px;
    height: auto;
    border-radius: 5px;
}
</style>

<script>
import DataTable from '../Common/DataTable';
import axios from 'axios';
import _ from 'lodash';
import AlbumArt from '../Common/AlbumArt';
import handleAxiosError from '../Function/handleAxiosError';

export default {
    components: { AlbumArt, DataTable },
    emits: ['submitted'],
    props: {
        requestListUri: {
            type: String,
            required: true
        },
        customFields: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    data () {
        let fields = [
            { key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: true, selectable: true },
            { key: 'song_title', label: this.$gettext('Title'), sortable: true, selectable: true, visible: false },
            {
                key: 'song_artist',
                label: this.$gettext('Artist'),
                sortable: true,
                selectable: true,
                visible: false
            },
            { key: 'song_album', label: this.$gettext('Album'), sortable: true, selectable: true, visible: false },
            { key: 'song_genre', label: this.$gettext('Genre'), sortable: true, selectable: true, visible: false }
        ];

        _.forEach(this.customFields.slice(), (field) => {
            fields.push({
                key: 'song_custom_fields_' + field.short_name,
                label: field.name,
                sortable: true,
                selectable: true,
                visible: false
            });
        });

        fields.push(
            { key: 'actions', label: this.$gettext('Actions'), sortable: false }
        );

        return {
            fields: fields,
            pageOptions: [
                10, 25
            ]
        };
    },
    computed: {
        langAlbumArt () {
            return this.$gettext('Album Art');
        }
    },
    methods: {
        doSubmitRequest (url) {
            axios.post(url).then((resp) => {
                notify('<b>' + resp.data.message + '</b>', 'success');
                this.$emit('submitted');
            }).catch((err) => {
                handleAxiosError(err);
                this.$emit('submitted');
            });
        }
    }
};
</script>
