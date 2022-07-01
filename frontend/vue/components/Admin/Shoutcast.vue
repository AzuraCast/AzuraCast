<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_title">Install Shoutcast 2 DNAS</translate>
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
                                <translate key="lang_instructions_1a">Shoutcast 2 DNAS is not free software, and its restrictive license does not allow AzuraCast to distribute the Shoutcast binary.</translate>
                            </p>

                            <p class="card-text">
                                <translate key="lang_instructions_1b">In order to install Shoutcast:</translate>
                            </p>

                            <ul>
                                <li>
                                    <translate key="lang_instructions_2">Download the Linux x64 binary from the Shoutcast Radio Manager:</translate>
                                    <br>
                                    <a href="https://radiomanager.shoutcast.com/register/serverSoftwareFreemium"
                                       target="_blank">
                                        <translate key="lang_instructions_2_url">Shoutcast Radio Manager</translate>
                                    </a>
                                </li>
                                <li>
                                    <translate key="lang_instructions_3">The file name should look like:</translate>
                                    <br>
                                    <code>sc_serv2_linux_x64-latest.tar.gz</code>
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

                            <p v-if="version" class="text-success card-text">
                                {{ langInstalledVersion }}
                            </p>
                            <p v-else class="text-danger card-text">
                                <translate
                                    key="lang_not_installed">Shoutcast 2 DNAS is not currently installed on this installation.</translate>
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
