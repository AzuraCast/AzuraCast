<template>
        <b-form-group>
            <b-form-row>
                <b-form-group class="col-md-6" label-for="stereo_tool_configuration_file">
                    <template #label>
                        <translate key="stereo_tool_configuration_file">Select Configuration File</translate>
                    </template>
                    <template #description>
                        <translate key="stereo_tool_configuration_file_desc">This configuration file should be a valid .sts file exported from Stereo Tool.</translate>
                    </template>

                    <flow-upload :target-url="targetUrl" :valid-mime-types="acceptMimeTypes"
                                 @success="onFileSuccess"></flow-upload>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        <translate key="existing_stereo_tool_configuration">Current Configuration File</translate>
                    </template>

                    <div v-if="hasStereoToolConfiguration">
                        <div class="buttons pt-3">
                            <b-button v-if="editConfigurationUrl" block variant="bg" :href="editConfigurationUrl" target="_blank">
                                <translate key="btn_download">Download</translate>
                            </b-button>
                            <b-button block variant="danger" @click="deleteConfigurationFile">
                                <translate key="btn_delete_stereo_tool_configuration">Clear File</translate>
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        <translate key="no_existing_stereo_tool_configuration">There is no existing Stereo Tool configuration file present.</translate>
                    </div>
                </b-form-group>
            </b-form-row>
        </b-form-group>
</template>

<script>
import FlowUpload from '~/components/Common/FlowUpload';

export default {
    name: 'StationStereoToolConfiguration',
    components: {FlowUpload},
    props: {
        value: Object,
        stationHasStereoToolConfiguration: Boolean,
        editConfigurationUrl: String,
        newConfigurationUrl: String
    },
    data() {
        return {
            hasStereoToolConfiguration: this.stationHasStereoToolConfiguration,
            acceptMimeTypes: ['text/plain']
        };
    },
    watch: {
        stationHasStereoToolConfiguration(newValue) {
            this.hasStereoToolConfiguration = newValue;
        }
    },
    computed: {
        targetUrl() {
            return (this.editConfigurationUrl)
                ? this.editConfigurationUrl
                : this.newConfigurationUrl;
        }
    },
    methods: {
        onFileSuccess(file, message) {
            this.hasStereoToolConfiguration = true;
            if (!this.editConfigurationUrl) {
                this.$emit('input', message);
            }
        },
        deleteConfigurationFile() {
            if (this.editConfigurationUrl) {
                this.axios.delete(this.editConfigurationUrl).then((resp) => {
                    this.hasStereoToolConfiguration = false;
                });
            } else {
                this.hasStereoToolConfiguration = false;
                this.$emit('input', null);
            }
        }
    }
};
</script>
