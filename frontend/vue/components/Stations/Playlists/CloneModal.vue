<template>
    <modal-form ref="modal" id="clone_modal" :title="langTitle" :error="error"
                :disable-save-button="$v.form.$invalid" @submit="doSubmit" @hidden="clearContents">

        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_edit_name" :field="$v.form.name">
                <template #label="{lang}">
                    <translate :key="lang">New Playlist Name</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="form_edit_clone" :field="$v.form.clone">
                <template #label="{lang}">
                    <translate :key="lang">Customize Copy</translate>
                </template>
                <template #default="props">
                    <b-form-checkbox-group stacked :id="props.id" v-model="props.field.$model">
                        <b-form-checkbox value="media">
                            <translate key="lang_clone_media">Copy associated media and folders.</translate>
                        </b-form-checkbox>
                        <b-form-checkbox value="schedule">
                            <translate key="lang_clone_schedule">Copy scheduled playback times.</translate>
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </b-form-row>

    </modal-form>
</template>

<script>
import {required} from 'vuelidate/dist/validators.min.js';
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import {validationMixin} from 'vuelidate';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";

export default {
    name: 'CloneModal',
    components: {ModalForm, BWrappedFormGroup, InvisibleSubmitButton},
    emits: ['relist', 'needs-restart'],
    mixins: [
        validationMixin
    ],
    data() {
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

            this.$wrapWithLoading(
                this.axios({
                    method: 'POST',
                    url: this.cloneUrl,
                    data: this.form
                })
            ).then((resp) => {
                this.$notifySuccess();
                this.$emit('needs-restart');
                this.$emit('relist');
                this.$refs.modal.hide();
            });
        },
        clearContents() {
            this.error = null;
            this.cloneUrl = null;
            this.resetForm();

            this.$v.form.$reset();
        }
    }
};
</script>
