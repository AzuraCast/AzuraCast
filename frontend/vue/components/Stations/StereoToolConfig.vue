<template>
    <section class="card" role="region">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_hdr">Upload Stereo Tool Configuration</translate>
            </h2>
        </div>

        <info-card>
            <p class="card-text">
                <translate key="lang_stereo_tool_desc">Stereo Tool is an industry standard for software audio processing. For more information on how to configure it, please refer to the</translate>
                <a href="https://www.thimeo.com/stereo-tool/" target="_blank">
                    <translate key="lang_stereo_tool_documentation_desc">Stereo Tool documentation.</translate>
                </a>
            </p>
        </info-card>

        <div class="card-body">
            <b-form-group>
                <b-form-row>
                    <b-form-group class="col-md-6" label-for="stereo_tool_configuration_file">
                        <template #label>
                            <translate key="stereo_tool_configuration_file">Select Configuration File</translate>
                        </template>
                        <template #description>
                            <translate key="stereo_tool_configuration_file_desc">This configuration file should be a valid .sts file exported from Stereo Tool.</translate>
                        </template>

                        <flow-upload :target-url="apiUrl" :valid-mime-types="acceptMimeTypes"
                                     @success="onFileSuccess"></flow-upload>
                    </b-form-group>

                    <b-form-group class="col-md-6">
                        <template #label>
                            <translate key="existing_stereo_tool_configuration">Current Configuration File</translate>
                        </template>
                        <div v-if="hasStereoToolConfiguration">
                            <div class="buttons pt-3">
                                <b-button block variant="bg" :href="apiUrl" target="_blank">
                                    <translate key="btn_download">Download</translate>
                                </b-button>
                                <b-button block variant="danger" @click="deleteConfigurationFile">
                                    <translate key="btn_delete_stereo_tool_configuration">Clear File</translate>
                                </b-button>
                            </div>
                        </div>
                        <div v-else>
                            <translate key="no_existing_stereo_tool_configuration">There is no Stereo Tool configuration file present.</translate>
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
import StationMayNeedRestart from '~/components/Stations/Common/MayNeedRestart.vue';

export default {
    name: 'StationsStereoToolConfiguration',
    components: {InfoCard, FlowUpload},
    mixins: [StationMayNeedRestart],
    props: {
        recordHasStereoToolConfiguration: Boolean,
        apiUrl: String
    },
    data() {
        return {
            hasStereoToolConfiguration: this.recordHasStereoToolConfiguration,
            acceptMimeTypes: ['text/plain']
        };
    },
    methods: {
        onFileSuccess(file, message) {
            this.mayNeedRestart();
            this.hasStereoToolConfiguration = true;
        },
        deleteConfigurationFile() {
            this.$wrapWithLoading(
                this.axios({
                    method: 'DELETE',
                    url: this.apiUrl
                })
            ).then((resp) => {
                this.mayNeedRestart();
                this.hasStereoToolConfiguration = false;
                this.$notifySuccess();
            });
        },
    }
};
</script>
