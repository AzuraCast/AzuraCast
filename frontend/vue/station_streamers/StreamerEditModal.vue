<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>
        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <form-basic-info :form="$v.form"></form-basic-info>
                <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                               :station-time-zone="stationTimeZone"></form-schedule>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
    import { validationMixin } from 'vuelidate';
    import axios from 'axios';
    import required from 'vuelidate/src/validators/required';
    import FormBasicInfo from './form/StreamerFormBasicInfo';
    import FormSchedule from './form/StreamerFormSchedule';
    import InvisibleSubmitButton from '../components/InvisibleSubmitButton';

    export default {
        name: 'EditModal',
        mixins: [validationMixin],
        components: { FormBasicInfo, FormSchedule, InvisibleSubmitButton },
        props: {
            createUrl: String,
            stationTimeZone: String
        },
        data () {
            return {
                loading: true,
                editUrl: null,
                form: {}
            };
        },
        validations () {
            let validations = {
                form: {
                    'streamer_username': { required },
                    'streamer_password': {},
                    'display_name': {},
                    'comments': {},
                    'is_active': {},
                    'enforce_schedule': {},
                    'schedule_items': {
                        $each: {
                            'start_time': { required },
                            'end_time': { required },
                            'start_date': {},
                            'end_date': {},
                            'days': {}
                        }
                    }
                }
            };

            if (this.editUrl === null) {
                validations.form.streamer_password = { required };
            }

            return validations;
        },
        computed: {
            langTitle () {
                return this.isEditMode
                    ? this.$gettext('Edit Streamer')
                    : this.$gettext('Add Streamer');
            },
            isEditMode () {
                return this.editUrl !== null;
            }
        },
        methods: {
            resetForm () {
                this.form = {
                    'streamer_username': null,
                    'streamer_password': null,
                    'display_name': null,
                    'comments': null,
                    'is_active': true,
                    'enforce_schedule': false,
                    'schedule_items': []
                };
            },
            create () {
                this.resetForm();
                this.loading = false;
                this.editUrl = null;

                this.$refs.modal.show();
            },
            edit (recordUrl) {
                this.resetForm();
                this.loading = true;
                this.editUrl = recordUrl;
                this.$refs.modal.show();

                axios.get(this.editUrl).then((resp) => {

                    let d = resp.data;

                    this.form = {
                        'streamer_username': d.streamer_username,
                        'streamer_password': null,
                        'display_name': d.display_name,
                        'comments': d.comments,
                        'is_active': d.is_active,
                        'enforce_schedule': d.enforce_schedule,
                        'schedule_items': d.schedule_items
                    };

                    this.loading = false;
                }).catch((err) => {
                    console.log(err);
                    this.close();
                });
            },
            doSubmit () {
                this.$v.form.$touch();
                if (this.$v.form.$anyError) {
                    return;
                }

                axios({
                    method: (this.isEditMode)
                        ? 'PUT'
                        : 'POST',
                    url: (this.isEditMode)
                        ? this.editUrl
                        : this.createUrl,
                    data: this.form
                }).then((resp) => {
                    let notifyMessage = this.$gettext('Changes saved.');
                    notify('<b>' + notifyMessage + '</b>', 'success', false);

                    this.$emit('relist');
                    this.close();
                }).catch((err) => {
                    console.error(err);

                    let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                    notify('<b>' + notifyMessage + '</b>', 'danger', false);

                    this.$emit('relist');
                    this.close();
                });
            },
            close () {
                this.loading = false;
                this.editUrl = null;
                this.resetForm();

                this.$v.form.$reset();
                this.$refs.modal.hide();
            }
        }
    };
</script>