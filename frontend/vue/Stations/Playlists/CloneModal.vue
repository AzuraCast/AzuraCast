<template>
    <b-modal size="md" id="clone_modal" ref="modal" :title="langTitle">
        <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

        <b-form class="form" @submit.prevent="doSubmit">
            <b-row>
                <b-form-group class="col-md-12" label-for="form_edit_name">
                    <template #label>
                        <translate key="lang_form_edit_name">New Playlist Name</translate>
                    </template>
                    <b-form-input id="form_edit_name" type="text" v-model="$v.form.name.$model"
                                  :state="$v.form.name.$dirty ? !$v.form.name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="edit_form_clone">
                    <template #label>
                        <translate key="lang_form_clone">Customize Copy</translate>
                    </template>

                    <b-form-checkbox-group stacked id="edit_form_clone" v-model="$v.form.clone.$model">
                        <b-form-checkbox value="media">
                            <translate key="lang_clone_media">Copy associated media and folders.</translate>
                        </b-form-checkbox>
                        <b-form-checkbox value="schedule">
                            <translate key="lang_clone_schedule">Copy scheduled playback times.</translate>
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </b-form-group>
            </b-row>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import axios from 'axios';
import handleAxiosError from '../../Function/handleAxiosError';
import { validationMixin } from 'vuelidate';

export default {
    name: 'CloneModal',
    components: { InvisibleSubmitButton },
    emits: ['relist'],
    mixins: [
        validationMixin
    ],
    data () {
        return {
            error: null,
            cloneUrl: null,
            form: {}
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Duplicate Playlist');
        }
    },
    validations: {
        form: {
            'name': { required },
            'clone': {}
        }
    },
    methods: {
        resetForm () {
            this.form = {
                'name': '',
                'clone': []
            };
        },
        open (name, cloneUrl) {
            this.error = null;

            this.resetForm();
            this.cloneUrl = cloneUrl;

            let langNewName = this.$gettext('%{name} - Copy');
            this.form.name = this.$gettextInterpolate(langNewName, { name: name });

            this.$refs.modal.show();
        },
        doSubmit () {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            axios({
                method: 'POST',
                url: this.cloneUrl,
                data: this.form
            }).then((resp) => {
                let notifyMessage = this.$gettext('Changes saved.');
                notify('<b>' + notifyMessage + '</b>', 'success');

                this.$emit('relist');
                this.close();
            }).catch((error) => {
                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                notifyMessage = handleAxiosError(error, notifyMessage);

                this.error = notifyMessage;
            });
        },
        close () {
            this.error = null;
            this.cloneUrl = null;
            this.resetForm();

            this.$v.form.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>
