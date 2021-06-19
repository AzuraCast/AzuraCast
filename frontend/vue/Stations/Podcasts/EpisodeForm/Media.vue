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
                    <b-form-file id="media_file" accept="audio/x-m4a, audio/mpeg" @input="uploadNewMedia"></b-form-file>
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

export default {
    name: 'EpisodeFormMedia',
    props: {
        value: Object,
        recordHasMedia: Boolean,
        downloadUrl: String,
        editMediaUrl: String,
        newMediaUrl: String
    },
    data () {
        return {
            hasMedia: this.recordHasMedia
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Media');
        }
    },
    methods: {
        uploadNewMedia (file) {
            if (!(file instanceof File)) {
                return;
            }

            let url = (this.editMediaUrl) ? this.editMediaUrl : this.newMediaUrl;
            let formData = new FormData();
            formData.append('art', file);

            axios.post(url, formData).then((resp) => {
                this.hasMedia = true;
                if (!this.editMediaUrl) {
                    this.$emit('input', resp.data);
                }
            }).catch((err) => {
                handleAxiosError(err);
            });
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
