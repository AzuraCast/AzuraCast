<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_title">Install Stereo Tool</translate>
            </h2>
        </div>

        <div class="card-body">
            <b-overlay variant="card" :show="loading">
                <b-form-row>
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                <translate key="lang_instructions">Instructions</translate>
                            </legend>

                            <p class="card-text">
                                <translate key="lang_instructions_1a">Stereo Tool is not free software, and its restrictive license does not allow AzuraCast to distribute the Stereo Tool binary.</translate>
                            </p>

                            <p class="card-text">
                                <translate key="lang_instructions_1b">In order to install Stereo Tool:</translate>
                            </p>

                            <ul>
                                <li>
                                    <translate key="lang_instructions_2">Download the appropriate binary from the Stereo Tool downloads page:</translate>
                                    <br>
                                    <a href="https://www.thimeo.com/stereo-tool/download/"
                                       target="_blank">
                                        <translate key="lang_instructions_2_url">Stereo Tool Downloads</translate>
                                    </a>
                                </li>
                                <li>
                                    <translate key="lang_instructions_3">For most installations, you should choose the "Command line version 64 bit". For Raspberry Pi devices, select "Raspberry Pi 3/4 64 bit command line".</translate>
                                </li>
                                <li>
                                    <translate key="lang_instructions_4">Upload the file on this page to automatically extract it into the proper directory.</translate>
                                </li>
                            </ul>
                        </fieldset>
                    </div>
                    <div class="col-md-5">
                        <fieldset class="mb-3">
                            <legend>
                                <translate key="lang_current_version">Current Installed Version</translate>
                            </legend>

                            <p v-if="isInstalled" class="text-success card-text">
                                <translate
                                    key="lang_installed">Stereo Tool is installed on this installation.</translate>
                            </p>
                            <p v-else class="text-danger card-text">
                                <translate
                                    key="lang_not_installed">Stereo Tool is not currently installed on this installation.</translate>
                            </p>
                        </fieldset>

                        <flow-upload :target-url="apiUrl" @complete="relist"></flow-upload>
                    </div>
                </b-form-row>
            </b-overlay>
        </div>
    </div>
</template>

<script>
import FlowUpload from "~/components/Common/FlowUpload";

export default {
    name: 'AdminStereoTool',
    components: {FlowUpload},
    props: {
        apiUrl: String
    },
    data() {
        return {
            loading: true,
            isInstalled: false,
        };
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.loading = true;
            this.axios.get(this.apiUrl).then((resp) => {
                this.isInstalled = resp.data.isInstalled;
                this.loading = false;
            });
        }
    }
}
</script>
