<template>
    <data-table ref="datatable" id="song_requests" paginated :fields="fields" :responsive="false"
                :api-url="requestListUri">
        <template v-slot:cell(actions)="row">
            <b-button-group size="sm">
                <b-button size="sm" variant="primary" @click.prevent="doSubmitRequest(row.item.request_url)">
                    <translate key="lang_btn_request">Request</translate>
                </b-button>
            </b-button-group>
        </template>
    </data-table>
</template>

<script>
import DataTable from './components/DataTable';
import axios from 'axios';

export default {
    components: { DataTable },
    props: {
        requestListUri: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            fields: [
                { key: 'song_title', label: this.$gettext('Title'), sortable: true },
                { key: 'song_artist', label: this.$gettext('Artist'), sortable: true },
                { key: 'song_album', label: this.$gettext('Album'), sortable: true, visible: false },
                { key: 'actions', label: this.$gettext('Actions'), sortable: false }
            ]
        };
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
