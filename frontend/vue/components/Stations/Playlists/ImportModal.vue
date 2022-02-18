<template>
    <b-modal id="import_modal" ref="modal" :title="langTitle">
        <div v-if="results">
            <p class="card-text">{{ results.message }}</p>

            <b-table-simple striped responsive style="max-height: 300px; overflow-y: scroll;">
                <b-thead>
                    <b-tr>
                        <b-th class="p-2">
                            <translate key="lang_playlist_results_original">Original Path</translate>
                            <br>
                            <translate key="lang_playlist_results_matched">Matched</translate>
                        </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody>
                    <b-tr v-for="row in results.import_results" :key="row.path">
                        <b-td class="p-2 text-monospace" style="overflow-x: auto;">
                            <pre class="mb-0">{{ row.path }}</pre>
                            <pre v-if="row.match" class="mb-0 text-success">{{ row.match }}</pre>
                            <pre v-else class="mb-0 text-danger">
                                <translate key="lang_playlist_results_no_match">No Match</translate>
                            </pre>
                        </b-td>
                    </b-tr>
                </b-tbody>
            </b-table-simple>
        </div>
        <b-form v-else class="form" @submit.prevent="doSubmit">
            <b-form-group label-for="import_modal_playlist_file">
                <template #label>
                    <translate key="lang_form_playlist_file">Select PLS/M3U File to Import</translate>
                </template>
                <template #description>
                    <translate key="lang_form_playlist_file_desc">AzuraCast will scan the uploaded file for matches in this station's music library. Media should already be uploaded before running this step. You can re-run this tool as many times as needed.</translate>
                </template>
                <b-form-file id="import_modal_playlist_file" v-model="playlistFile"></b-form-file>
            </b-form-group>

            <invisible-submit-button/>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button v-if="!results" variant="primary" type="submit" @click="doSubmit">
                <translate key="lang_btn_import">Import from PLS/M3U</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';

export default {
    name: 'PlaylistImportModal',
    components: { InvisibleSubmitButton },
    data () {
        return {
            importPlaylistUrl: null,
            playlistFile: null,
            results: null,
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Import from PLS/M3U');
        }
    },
    methods: {
        open (importPlaylistUrl) {
            this.playlistFile = null;
            this.importPlaylistUrl = importPlaylistUrl;

            this.$refs.modal.show();
        },
        doSubmit () {
            let formData = new FormData();
            formData.append('playlist_file', this.playlistFile);

            this.$wrapWithLoading(
                this.axios.post(this.importPlaylistUrl, formData)
            ).then((resp) => {
                if (resp.data.success) {
                    this.results = resp.data;

                    this.$notifySuccess(resp.data.message);
                } else {
                    this.$notifyError(resp.data.message);
                    this.close();
                }
            });
        },
        close () {
            this.$emit('relist');
            this.$refs.modal.hide();
        }
    }
};
</script>
