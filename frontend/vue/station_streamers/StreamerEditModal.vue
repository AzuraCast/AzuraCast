<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>
        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-form-group>
                <b-row>
                    <b-form-group class="col-md-12" label-for="form_edit_is_active">
                        <template v-slot:description>
                            <translate>Enable to allow this account to log in and stream.</translate>
                        </template>
                        <b-form-checkbox id="form_edit_is_active" v-model="$v.form.is_active.$model">
                            <translate>Account is Active</translate>
                        </b-form-checkbox>
                    </b-form-group>
                </b-row>
                <b-row>
                    <b-form-group class="col-md-6" label-for="edit_form_streamer_username">
                        <template v-slot:label>
                            <translate>Streamer Username</translate>
                        </template>
                        <template v-slot:description>
                            <translate>The streamer will use this username to connect to the radio server.</translate>
                        </template>
                        <b-form-input type="text" id="edit_form_streamer_username" v-model="$v.form.streamer_username.$model"
                                      :state="$v.form.streamer_username.$dirty ? !$v.form.streamer_username.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate>This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                    <b-form-group class="col-md-6" label-for="edit_form_streamer_password">
                        <template v-slot:label>
                            <translate>Streamer password</translate>
                        </template>
                        <template v-slot:description>
                            <translate>The streamer will use this password to connect to the radio server.</translate>
                        </template>
                        <b-form-input type="password" id="edit_form_streamer_password" v-model="$v.form.streamer_password.$model"
                                      :state="$v.form.streamer_password.$dirty ? !$v.form.streamer_password.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate>This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                </b-row>
                <b-row>
                    <b-form-group class="col-md-6" label-for="edit_form_display_name">
                        <template v-slot:label>
                            <translate>Streamer Display Name</translate>
                        </template>
                        <template v-slot:description>
                            <translate>This is the informal display name that will be shown in API responses if the streamer/DJ is live.</translate>
                        </template>
                        <b-form-input type="text" id="edit_form_display_name" v-model="$v.form.display_name.$model"
                                      :state="$v.form.display_name.$dirty ? !$v.form.display_name.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate>This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                    <b-form-group class="col-md-6" label-for="edit_form_comments">
                        <template v-slot:label>
                            <translate>Comments</translate>
                        </template>
                        <template v-slot:description>
                            <translate>Internal notes or comments about the user, visible only on this control panel.</translate>
                        </template>
                        <b-form-textarea id="edit_form_comments" v-model="$v.form.comments.$model"
                                         :state="$v.form.comments.$dirty ? !$v.form.comments.$error : null"></b-form-textarea>
                        <b-form-invalid-feedback>
                            <translate>This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                </b-row>
            </b-form-group>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate>Close</translate>
            </b-button>
            <b-button variant="primary" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate>Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
    import { validationMixin } from 'vuelidate';
    import axios from 'axios';
    import required from 'vuelidate/src/validators/required';

    export default {
        name: 'EditModal',
        mixins: [validationMixin],
        props: {
            createUrl: String
        },
        data () {
            return {
                loading: true,
                editUrl: null,
                form: {}
            };
        },
        validations: {
            form: {
                'streamer_username': { required },
                'streamer_password': {},
                'display_name': {},
                'comments': {},
                'is_active': {}
            }
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
                    'is_active': true
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
                        'is_active': d.is_active
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