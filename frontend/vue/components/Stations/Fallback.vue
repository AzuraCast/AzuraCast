<template>
    <section class="card" role="region">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_hdr">Custom Fallback File</translate>
            </h2>
        </div>

        <info-card>
            <p class="card-text">
                <translate key="lang_fallback_1">This file will be played on your radio station any time no media is scheduled to play or a critical error occurs that interrupts regular broadcasting.</translate>
            </p>
        </info-card>

        <div class="card-body">
            <b-form-group>
                <b-form-row>
                    <b-form-group class="col-md-6" label-for="intro_file">
                        <template #label>
                            <translate key="intro_file">Select Custom Fallback File</translate>
                        </template>

                        <flow-upload :target-url="apiUrl" :valid-mime-types="acceptMimeTypes"
                                     @success="onFileSuccess"></flow-upload>
                    </b-form-group>

                    <b-form-group class="col-md-6">
                        <template #label>
                            <translate key="existing_intro">Current Custom Fallback File</translate>
                        </template>

                        <div v-if="hasFallback">
                            <div class="buttons pt-3">
                                <b-button block variant="bg" :href="apiUrl"
                                          target="_blank">
                                    <translate key="btn_download">Download</translate>
                                </b-button>
                                <b-button block variant="danger" @click="deleteFallback">
                                    <translate key="btn_delete_fallback">Clear File</translate>
                                </b-button>
                            </div>
                        </div>
                        <div v-else>
                            <translate key="no_existing_fallback">There is no existing custom fallback file associated with this station.</translate>
                        </div>
                    </b-form-group>
                </b-form-row>
            </b-form-group>
        </div>
    </section>
</template>

<script>
import FlowUpload from '~/components/Common/FlowUpload';
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'StationsFallback',
    components: {InfoCard, FlowUpload},
    props: {
        apiUrl: String,
        recordHasFallback: Boolean
    },
    data() {
        return {
            hasFallback: this.recordHasFallback,
            acceptMimeTypes: ['audio/*']
        };
    },
    methods: {
        onFileSuccess(file, message) {
            this.hasFallback = true;
        },
        deleteFallback() {
            this.axios.delete(this.apiUrl).then((resp) => {
                this.hasFallback = false;
            });
        }
    }
};
</script>
