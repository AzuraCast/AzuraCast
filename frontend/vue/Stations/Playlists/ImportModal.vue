<template>
    <b-modal id="import_modal" ref="modal" :title="langTitle">
        <b-form class="form" @submit.prevent="doSubmit">
            <b-form-group label-for="import_modal_playlist_file">
                <template v-slot:label>
                    <translate key="lang_form_playlist_file">Select PLS/M3U File to Import</translate>
                </template>
                <template v-slot:description>
                    <translate key="lang_form_playlist_file_desc">AzuraCast will scan the uploaded file for matches in this station's music library. Media should already be uploaded before running this step. You can re-run this tool as many times as needed.</translate>
                </template>
                <b-form-file id="import_modal_playlist_file" v-model="playlistFile"></b-form-file>
            </b-form-group>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit">
                <translate key="lang_btn_import">Import from PLS/M3U</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import axios from 'axios';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';

export default {
    name: 'PlaylistImportModal',
    components: { InvisibleSubmitButton },
    data () {
        return {
            importPlaylistUrl: null,
            playlistFile: null
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

            axios.post(this.importPlaylistUrl, formData).then((resp) => {
                if (resp.data.success) {
                    notify('<b>' + resp.data.message + '</b>', 'success');
                } else {
                    notify('<b>' + resp.data.message + '</b>', 'danger');
                }

                this.$emit('relist');
                this.close();
            }).catch((err) => {
                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                handleAxiosError(err, notifyMessage);

                this.$emit('relist');
                this.close();
            });
        },
        close () {
            this.$refs.modal.hide();
        }
    }
};
</script>
