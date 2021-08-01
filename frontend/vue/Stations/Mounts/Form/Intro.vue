<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="intro_file">
                    <template #label>
                        <translate key="intro_file">Select Intro File</translate>
                    </template>
                    <template #description>
                        <translate key="intro_file_desc">This introduction file should exactly match the bitrate and format of the mount point itself.</translate>
                    </template>

                    <flow-upload :target-url="targetUrl" :valid-mime-types="acceptMimeTypes"
                                 @success="onFileSuccess"></flow-upload>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        <translate key="existing_intro">Current Intro File</translate>
                    </template>

                    <div v-if="hasIntro">
                        <div class="buttons pt-3">
                            <b-button v-if="editIntroUrl" block variant="bg" :href="editIntroUrl" target="_blank">
                                <translate key="btn_download">Download</translate>
                            </b-button>
                            <b-button block variant="danger" @click="deleteIntro">
                                <translate key="btn_delete_intro">Clear File</translate>
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        <translate key="no_existing_intro">There is no existing intro file associated with this mount point.</translate>
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
    name: 'MountFormIntro',
    components: {FlowUpload},
    props: {
        value: Object,
        recordHasIntro: Boolean,
        editIntroUrl: String,
        newIntroUrl: String
    },
    data() {
        return {
            hasIntro: this.recordHasIntro,
            acceptMimeTypes: ['audio/*']
        };
    },
    watch: {
        recordHasIntro(newValue) {
            this.hasIntro = newValue;
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Intro');
        },
        targetUrl() {
            return (this.editIntroUrl)
                ? this.editIntroUrl
                : this.newIntroUrl;
        }
    },
    methods: {
        onFileSuccess(file, message) {
            this.hasIntro = true;
            if (!this.editIntroUrl) {
                this.$emit('input', message);
            }
        },
        deleteIntro() {
            if (this.editIntroUrl) {
                axios.delete(this.editIntroUrl).then((resp) => {
                    this.hasIntro = false;
                }).catch((err) => {
                    handleAxiosError(err);
                });
            } else {
                this.hasIntro = false;
                this.$emit('input', null);
            }
        }
    }
};
</script>
