<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-stations-clone-modal-form :form="v$.form"></admin-stations-clone-modal-form>

    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import ModalForm from "~/components/Common/ModalForm";
import AdminStationsCloneModalForm from "~/components/Admin/Stations/CloneModalForm";

export default {
    name: 'AdminStationsCloneModal',
    components: {AdminStationsCloneModalForm, ModalForm},
    setup() {
        return {v$: useVuelidate()}
    },
    emits: ['relist'],
    data() {
        return {
            loading: true,
            cloneUrl: null,
            error: null,
            form: {},
        }
    },
    validations() {
        return {
            form: {
                name: {required},
                description: {},
                clone: {}
            }
        };
    },
    computed: {
        langTitle() {
            return this.$gettext('Clone Station');
        },
    },
    methods: {
        resetForm() {
            this.form = {
                name: '',
                description: '',
                clone: [],
            };
        },
        create(stationName, cloneUrl) {
            this.resetForm();

            const newStationName = this.$gettext('%{station} - Copy');
            this.form.name = this.$gettextInterpolate(newStationName, {station: stationName});

            this.loading = false;
            this.error = null;
            this.cloneUrl = cloneUrl;

            this.$refs.modal.show();
        },
        clearContents() {
            this.resetForm();
            this.cloneUrl = null;
        },
        close() {
            this.$refs.modal.hide();
        },
        doSubmit() {
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
                this.$emit('relist');
                this.close();
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
    }
}
</script>
