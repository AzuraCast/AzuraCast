<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Install Shoutcast 2 DNAS') }}
            </h2>
        </div>

        <div class="card-body">
            <b-overlay variant="card" :show="loading">
                <b-form-row>
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                {{ $gettext('Instructions') }}
                            </legend>

                            <p class="card-text">
                                {{
                                    $gettext('Shoutcast 2 DNAS is not free software, and its restrictive license does not allow AzuraCast to distribute the Shoutcast binary.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install Shoutcast:') }}
                            </p>

                            <ul>
                                <li>
                                    {{ $gettext('Download the Linux x64 binary from the Shoutcast Radio Manager:') }}
                                    <br>
                                    <a href="https://radiomanager.shoutcast.com/register/serverSoftwareFreemium"
                                       target="_blank">
                                        {{ $gettext('Shoutcast Radio Manager') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('The file name should look like:') }}
                                    <br>
                                    <code>sc_serv2_linux_x64-latest.tar.gz</code>
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
                                {{ $gettext('Shoutcast 2 DNAS is not currently installed on this installation.') }}
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
    name: 'AdminShoutcast',
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
            const text = this.$gettext('Shoutcast version "%{ version }" is currently installed.');
            return this.$gettextInterpolate(text, {
                version: this.version
            });
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
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
