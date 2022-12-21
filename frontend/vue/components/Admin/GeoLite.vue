<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Install GeoLite IP Database') }}
            </h2>
        </div>

        <info-card>
            {{
                $gettext('IP Geolocation is used to guess the approximate location of your listeners based on the IP address they connect with. Use the free built-in IP Geolocation library or enter a license key on this page to use MaxMind GeoLite.')
            }}
        </info-card>

        <div class="card-body">
            <b-overlay variant="card" :show="loading">
                <div class="form-row">
                    <div class="col-md-7">
                        <fieldset>
                            <legend>{{ $gettext('Instructions') }}</legend>

                            <p class="card-text">
                                {{
                                    $gettext('AzuraCast ships with a built-in free IP geolocation database. You may prefer to use the MaxMind GeoLite service instead to achieve more accurate results. Using MaxMind GeoLite requires a license key, but once the key is provided, we will automatically keep the database updated.')
                                }}
                            </p>
                            <p class="card-text">
                                {{ $gettext('To download the GeoLite database:') }}
                            </p>
                            <ul>
                                <li>
                                    {{ $gettext('Create an account on the MaxMind developer site.') }}
                                    <br>
                                    <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">
                                        {{ $gettext('MaxMind Developer Site') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('Visit the "My License Key" page under the "Services" section.') }}
                                </li>
                                <li>
                                    {{ $gettext('Click "Generate new license key".') }}
                                </li>
                                <li>
                                    {{ $gettext('Paste the generated license key into the field on this page.') }}
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
                                {{ $gettext('GeoLite is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <form @submit.prevent="doUpdate">
                            <fieldset>
                                <b-wrapped-form-group id="edit_form_key" :field="v$.key">
                                    <template #label>
                                        {{ $gettext('MaxMind License Key') }}
                                    </template>
                                </b-wrapped-form-group>
                            </fieldset>

                            <div class="buttons">
                                <b-button variant="primary" type="submit">
                                    {{ $gettext('Save Changes') }}
                                </b-button>
                                <b-button variant="danger" type="button" @click.prevent="doDelete">
                                    {{ $gettext('Remove Key') }}
                                </b-button>
                            </div>
                        </form>
                    </div>
                </div>
            </b-overlay>
        </div>
    </div>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import '~/vendor/sweetalert.js';
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'GeoLite',
    components: {InfoCard, BWrappedFormGroup, BFormFieldset},
    setup() {
        return {v$: useVuelidate()}
    },
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
