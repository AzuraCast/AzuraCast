<template>
    <data-table ref="datatable" id="song_requests" paginated select-fields :fields="fields" :responsive="false"
                :api-url="requestListUri">
        <template v-slot:cell(name)="row">
            <a :href="row.item.song_art" class="album-art float-left pr-3" target="_blank"
               v-if="row.item.song_art" data-fancybox="gallery">
                <img class="album_art" :alt="langAlbumArt" :src="row.item.song_art">
            </a>

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
import DataTable from './components/DataTable';
import axios from 'axios';
import _ from 'lodash';

export default {
    components: { DataTable },
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
            fields: fields
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
                console.error(err);
                if (err.response.message) {
                    notify('<b>' + err.response.message + '</b>', 'danger');
                }
                this.$emit('submitted');
            });
        }
    }
};
</script>
