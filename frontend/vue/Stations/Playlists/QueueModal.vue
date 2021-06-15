<template>
    <b-modal size="lg" id="queue_modal" ref="modal" :title="langTitle" :busy="loading">
        <p>
            <translate key="queue_info">This queue contains the remaining tracks in the order they will be queued by the AzuraCast AutoDJ (if the tracks are eligible to be played).</translate>
        </p>
        <b-overlay variant="card" :show="loading">
            <b-table-simple striped class="sortable mb-0">
                <b-thead>
                    <tr>
                        <th style="width: 50%;" key="lang_col_title" v-translate>Title</th>
                        <th style="width: 50%;" key="lang_col_artist" v-translate>Artist</th>
                    </tr>
                </b-thead>
                <b-tbody>
                    <tr class="align-middle" v-for="(row,index) in media" :key="row.id">
                        <td><big>{{ row.title }}</big></td>
                        <td>{{ row.artist }}</td>
                    </tr>
                </b-tbody>
            </b-table-simple>
        </b-overlay>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="danger" type="submit" @click="doClear">
                <translate key="lang_btn_clear_queue">Clear Queue</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import axios from 'axios';
import handleAxiosError from '../../Function/handleAxiosError';

export default {
    name: 'QueueModal',
    data () {
        return {
            loading: true,
            queueUrl: null,
            media: []
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Playback Queue');
        }
    },
    methods: {
        open (queueUrl) {
            this.$refs.modal.show();
            this.queueUrl = queueUrl;
            this.loading = true;

            axios.get(this.queueUrl).then((resp) => {
                this.media = resp.data;
                this.loading = false;
            }).catch((err) => {
                handleAxiosError(err);
            });
        },
        doClear () {
            axios.delete(this.queueUrl).then((resp) => {
                notify('<b>' + this.$gettext('Playlist queue cleared.') + '</b>', 'success');
                this.close();
            }).catch((err) => {
                handleAxiosError(err);
            });
        },
        close () {
            this.loading = false;
            this.error = null;
            this.queueUrl = null;

            this.$refs.modal.hide();
        }
    }
};
</script>
