<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_title">Install GeoLite IP Database</translate>
            </h2>
        </div>

        <info-card>
            <translate key="lang_info_card">IP Geolocation is used to guess the approximate location of your listeners based on the IP address they connect with. Use the free built-in IP Geolocation library or enter a license key on this page to use MaxMind GeoLite.</translate>
        </info-card>

        <div class="card-body">
            <b-overlay variant="card" :show="loading">
                <b-form-row>
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                <translate key="lang_instructions">Instructions</translate>
                            </legend>

                            <p class="card-text">
                                <translate key="lang_instructions_1a">AzuraCast ships with a built-in free IP geolocation database. You may prefer to use the MaxMind GeoLite service instead to achieve more accurate results. Using MaxMind GeoLite requires a license key, but once the key is provided, we will automatically keep the database updated.</translate>
                            </p>
                            <p class="card-text">
                                <translate key="lang_instructions_1b">To download the GeoLite database:</translate>
                            </p>
                            <ul>
                                <li>
                                    <translate
                                        key="lang_instructions_2">Create an account on the MaxMind developer site.</translate>
                                    <br>
                                    <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">
                                        <translate key="lang_instructions_2_link">MaxMind Developer Site</translate>
                                    </a>
                                </li>
                                <li>
                                    <translate key="lang_instructions_3">Visit the "My License Key" page under the "Services" section.</translate>
                                </li>
                                <li>
                                    <translate key="lang_instructions_4">Click "Generate new license key".</translate>
                                </li>
                                <li>
                                    <translate key="lang_instructions_5">Paste the generated license key into the field on this page.</translate>
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
                                    key="lang_not_installed">GeoLite is not currently installed on this installation.</translate>
                            </p>
                        </fieldset>

                        <form @submit.prevent="doUpdate">
                            <fieldset>
                                <b-wrapped-form-group id="edit_form_key" :field="$v.key">
                                    <template #label>
                                        <translate key="lang_edit_form_key">MaxMind License Key</translate>
                                    </template>
                                </b-wrapped-form-group>
                            </fieldset>

                            <div class="buttons">
                                <b-button variant="primary" type="submit">
                                    <translate key="btn_save_changes">Save Changes</translate>
                                </b-button>
                                <b-button variant="danger" type="button" @click.prevent="doDelete">
                                    <translate key="btn_remove_key">Remove Key</translate>
                                </b-button>
                            </div>
                        </form>
                    </div>
                </b-form-row>
            </b-overlay>
        </div>
    </div>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {validationMixin} from "vuelidate";
import '~/vendor/sweetalert.js';
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'GeoLite',
    components: {InfoCard, BWrappedFormGroup, BFormFieldset},
    mixins: [
        validationMixin
    ],
    props: {
        apiUrl: String
    },
    data() {
        return {
            loading: true,
            key: null,
            version: null
        };
    },
    validations: {
        key: {}
    },
    computed: {
        langInstalledVersion() {
            const text = this.$gettext('GeoLite version "%{ version }" is currently installed.');
            return this.$gettextInterpolate(text, {
                version: this.version
            });
        }
    },
    mounted() {
        this.doFetch();
    },
    methods: {
        doFetch() {
            this.loading = true;
            this.axios.get(this.apiUrl).then((resp) => {
                this.key = resp.data.key;
                this.version = resp.data.version;

                this.loading = false;
            });
        },
        doUpdate() {
            this.loading = true;
            this.$wrapWithLoading(
                this.axios.post(this.apiUrl, {
                    geolite_license_key: this.key
                })
            ).then((resp) => {
                this.version = resp.data.version;
            }).finally(() => {
                this.loading = false;
            });
        },
        doDelete() {
            this.$confirmDelete().then((result) => {
                if (result.value) {
                    this.key = null;
                    this.doUpdate();
                }
            });
        }
    }
}
</script>
