<template>
    <b-modal size="lg" id="queue_modal" ref="modal" :title="langTitle" :busy="loading">
        <p>
            {{
                $gettext('This queue contains the remaining tracks in the order they will be queued by the AzuraCast AutoDJ (if the tracks are eligible to be played).')
            }}
        </p>
        <b-overlay variant="card" :show="loading">
            <b-table-simple striped class="sortable mb-0">
                <b-thead>
                    <tr>
                        <th style="width: 50%;">{{ $gettext('Title') }}</th>
                        <th style="width: 50%;">{{ $gettext('Artist') }}</th>
                    </tr>
                </b-thead>
                <b-tbody>
                    <tr class="align-middle" v-for="(row,index) in media" :key="row.id">
                        <td>
                            <span class="typography-subheading">{{ row.title }}</span>
                        </td>
                        <td>{{ row.artist }}</td>
                    </tr>
                </b-tbody>
            </b-table-simple>
        </b-overlay>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button variant="danger" type="submit" @click="doClear">
                {{ $gettext('Clear Queue') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>

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

            this.axios.get(this.queueUrl).then((resp) => {
                this.media = resp.data;
                this.loading = false;
            });
        },
        doClear () {
            this.$wrapWithLoading(
                this.axios.delete(this.queueUrl)
            ).then(() => {
                this.$notifySuccess(this.$gettext('Playlist queue cleared.'));
                this.close();
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
