<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="media_file">
                    <template #label>
                        <translate key="media_file">Select Media File</translate>
                    </template>
                    <template #description>
                        <translate key="media_file_desc">Podcast media should be in the MP3 or M4A (AAC) format for the greatest compatibility.</translate>
                    </template>

                    <flow-upload :target-url="targetUrl" :valid-mime-types="acceptMimeTypes" @success="onFileSuccess"></flow-upload>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        <translate key="existing_media">Current Podcast Media</translate>
                    </template>

                    <div v-if="hasMedia">
                        <div class="buttons pt-3">
                            <b-button v-if="downloadUrl" block variant="bg" :href="downloadUrl" target="_blank">
                                <translate key="btn_download">Download</translate>
                            </b-button>
                            <b-button block variant="danger" @click="deleteMedia">
                                <translate key="btn_delete_media">Clear Media</translate>
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        <translate key="no_existing_media">There is no existing media associated with this episode.</translate>
                    </div>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import axios from 'axios';
import handleAxiosError from '../../../Function/handleAxiosError';
import FlowUpload from '../../../Common/FlowUpload';

export default {
    name: 'EpisodeFormMedia',
    components: { FlowUpload },
    props: {
        value: Object,
        recordHasMedia: Boolean,
        downloadUrl: String,
        editMediaUrl: String,
        newMediaUrl: String
    },
    data() {
        return {
            hasMedia: this.recordHasMedia,
            acceptMimeTypes: ['audio/x-m4a', 'audio/mpeg']
        };
    },
    watch: {
        recordHasMedia(newValue) {
            this.hasMedia = newValue;
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Media');
        },
        targetUrl() {
            return (this.editMediaUrl)
                ? this.editMediaUrl
                : this.newMediaUrl;
        }
    },
    methods: {
        onFileSuccess (file, message) {
            this.hasMedia = true;
            if (!this.editMediaUrl) {
                this.$emit('input', message);
            }
        },
        deleteMedia () {
            if (this.editMediaUrl) {
                axios.delete(this.editMediaUrl).then((resp) => {
                    this.hasMedia = false;
                }).catch((err) => {
                    handleAxiosError(err);
                });
            } else {
                this.hasMedia = false;
                this.$emit('input', null);
            }
        }
    }
};
</script>
