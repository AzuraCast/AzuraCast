<template>
    <modal-form
        id="clone_modal"
        ref="modal"
        :title="$gettext('Duplicate Playlist')"
        :error="error"
        :disable-save-button="v$.form.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="form-row">
            <b-wrapped-form-group
                id="form_edit_name"
                class="col-md-12"
                :field="v$.form.name"
            >
                <template #label>
                    {{ $gettext('New Playlist Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_edit_clone"
                class="col-md-12"
                :field="v$.form.clone"
            >
                <template #label>
                    {{ $gettext('Customize Copy') }}
                </template>
                <template #default="slotProps">
                    <b-form-checkbox-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                    >
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
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";

export default {
    name: 'CloneModal',
    components: {ModalForm, BWrappedFormGroup},
    emits: ['relist', 'needs-restart'],
    setup() {
        return {v$: useVuelidate()}
    },
    data() {
        return {
            error: null,
            cloneUrl: null,
            form: {}
        };
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

            this.form.name = this.$gettext(
                '%{name} - Copy',
                {name: name}
            );

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
