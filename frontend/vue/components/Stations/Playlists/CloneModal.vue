<template>
    <modal-form ref="modal" id="clone_modal" :title="langTitle" :error="error"
                :disable-save-button="v$.form.$invalid" @submit="doSubmit" @hidden="clearContents">

        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_edit_name" :field="v$.form.name">
                <template #label>
                    {{ $gettext('New Playlist Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="form_edit_clone" :field="v$.form.clone">
                <template #label>
                    {{ $gettext('Customize Copy') }}
                </template>
                <template #default="props">
                    <b-form-checkbox-group stacked :id="props.id" v-model="props.field.$model">
                        <b-form-checkbox value="media">
                            {{ $gettext('Copy associated media and folders.') }}
                        </b-form-checkbox>
                        <b-form-checkbox value="schedule">
                            {{ $gettext('Copy scheduled playback times.') }}
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </div>

    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";

export default {
    name: 'CloneModal',
    components: {ModalForm, BWrappedFormGroup, InvisibleSubmitButton},
    setup() {
        return {v$: useVuelidate()}
    },
    emits: ['relist', 'needs-restart'],
    data() {
        return {
            error: null,
            cloneUrl: null,
            form: {}
        };
    },
    computed: {
        langTitle() {
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
            this.form.name = this.$gettextInterpolate(langNewName, {name: name});

            this.$refs.modal.show();
        },
        async doSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: 'POST',
                    url: this.cloneUrl,
                    data: this.form
                })
            ).then(() => {
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

            this.v$.$reset();
        }
    }
};
</script>
