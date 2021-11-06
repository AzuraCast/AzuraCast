<template>
    <div>
        <section class="card mb-3" role="region">
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title">
                    <translate key="lang_hdr_automated_assignment">Automated Assignment</translate>
                </h2>
            </b-card-header>

            <div class="card-body">
                <p class="card-text">
                    <translate key="lang_automated_1">Based on the previous performance of your station's songs, AzuraCast can automatically distribute songs evenly among your playlists, placing the highest performing songs in the highest-weighted playlists.</translate>
                </p>
                <p class="card-text">
                    <translate key="lang_automated_2">Once you have configured automated assignment, click the button below to run the automated assignment process.</translate>
                </p>

                <b-button variant="warning" :disabled="!settings.is_enabled" @click.prevent="doRun">
                    <translate key="lang_btn_run">Run Automated Assignment</translate>
                </b-button>
            </div>
        </section>

        <form class="form vue-form" @submit.prevent="submit">
            <section class="card mb-3" role="region">
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        <translate key="lang_hdr_configure">Configure Automated Assignment</translate>
                    </h2>
                </b-card-header>

                <b-overlay variant="card" :show="settingsLoading">
                    <div class="card-body">

                        <b-form-fieldset>
                            <b-wrapped-form-checkbox id="edit_form_is_enabled"
                                                     :field="$v.settings.is_enabled">
                                <template #label="{lang}">
                                    <translate :key="lang">Enable Automated Assignment</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">Allow the system to periodically automatically assign songs to playlists based on their performance. This process will run in the background, and will only run if this option is set to "Enabled" and at least one playlist is set to "Include in Automated Assignment".</translate>
                                </template>
                            </b-wrapped-form-checkbox>

                            <b-wrapped-form-group id="edit_form_threshold_days" :field="$v.settings.threshold_days">
                                <template #label="{lang}">
                                    <translate :key="lang">Days Between Automated Assignments</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">Based on this setting, the system will automatically reassign songs every (this) days using data from the previous (this) days.</translate>
                                </template>
                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                                        :options="thresholdDaysOptions"></b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                        </b-form-fieldset>

                        <b-button size="lg" type="submit" variant="primary" :disabled="$v.settings.$invalid">
                            <slot name="submitButtonName">
                                <translate key="lang_btn_save_changes">Save Changes</translate>
                            </slot>
                        </b-button>
                    </div>
                </b-overlay>
            </section>
        </form>
    </div>
</template>

<script>
import {validationMixin} from "vuelidate";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'StationsAutomation',
    components: {BFormFieldset, BWrappedFormGroup, BWrappedFormCheckbox},
    mixins: [
        validationMixin
    ],
    props: {
        settingsUrl: String,
        runUrl: String
    },
    data() {
        return {
            settingsLoading: true,
            settings: {
                is_enabled: false,
                threshold_days: 7
            }
        }
    },
    validations: {
        settings: {
            is_enabled: {},
            threshold_days: {}
        }
    },
    computed: {
        thresholdDaysOptions() {
            const langDays = this.$gettext('%{ days } Days');

            return [
                {value: '7', text: this.$gettextInterpolate(langDays, {days: 7})},
                {value: '14', text: this.$gettextInterpolate(langDays, {days: 14})},
                {value: '30', text: this.$gettextInterpolate(langDays, {days: 30})},
                {value: '60', text: this.$gettextInterpolate(langDays, {days: 60})}
            ];
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.$v.settings.$reset();
            this.settingsLoading = true;

            this.axios.get(this.settingsUrl).then((resp) => {
                this.settings = mergeExisting(this.settings, resp.data);
                this.settingsLoading = false;
            });
        },
        submit() {
            this.$v.settings.$touch();
            if (this.$v.settings.$anyError) {
                return;
            }

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.settingsUrl,
                    data: this.settings
                })
            ).then((resp) => {
                this.$notifySuccess();
                this.relist();
            });
        },
        doRun() {
            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.runUrl
                })
            ).then((resp) => {
                this.$notifySuccess();
            });
        }
    }
}
</script>
