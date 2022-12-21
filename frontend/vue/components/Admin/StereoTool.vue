<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Install Stereo Tool') }}
            </h2>
        </div>

        <div class="card-body">
            <b-overlay variant="card" :show="loading">
                <div class="form-row">
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                {{ $gettext('Instructions') }}
                            </legend>

                            <p class="card-text">
                                {{
                                    $gettext('Stereo Tool can be resource-intensive for both CPU and Memory. Please ensure you have sufficient resources before proceeding.')
                                }}
                            </p>

                            <p class="card-text">
                                {{
                                    $gettext('Stereo Tool is not free software, and its restrictive license does not allow AzuraCast to distribute the Stereo Tool binary.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install Stereo Tool:') }}
                            </p>

                            <ul>
                                <li>
                                    {{
                                        $gettext('Download the appropriate binary from the Stereo Tool downloads page:')
                                    }}
                                    <br>
                                    <a href="https://www.thimeo.com/stereo-tool/download/"
                                       target="_blank">
                                        {{ $gettext('Stereo Tool Downloads') }}
                                    </a>
                                </li>
                                <li>
                                    {{
                                        $gettext('For most installations, you should choose the "Command line version 64 bit". For Raspberry Pi devices, select "Raspberry Pi 3/4 64 bit command line".')
                                    }}
                                </li>
                                <li>
                                    {{
                                        $gettext('Upload the file on this page to automatically extract it into the proper directory.')
                                    }}
                                </li>
                            </ul>
                        </fieldset>
                    </div>
                    <div class="col-md-5">
                        <fieldset class="mb-3">
                            <legend>
                                {{ $gettext('Current Installed Version') }}
                            </legend>

                            <p v-if="version" class="text-success card-text">
                                {{ langInstalledVersion }}
                            </p>
                            <p v-else class="text-danger card-text">
                                {{ $gettext('Stereo Tool is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <flow-upload :target-url="apiUrl" @complete="relist" @error="onError"></flow-upload>
                    </div>
                </div>
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
            version: null,
        };
    },
    computed: {
        langInstalledVersion() {
            const text = this.$gettext('Stereo Tool version %{ version } is currently installed.');
            return this.$gettextInterpolate(text, {
                version: this.version
            });
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        onError(file, message) {
            this.$notifyError(message);
        },
        relist() {
            this.loading = true;
            this.axios.get(this.apiUrl).then((resp) => {
                this.version = resp.data.version;
                this.loading = false;
            });
        }
    }
}
</script>
