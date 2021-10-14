<template>
    <b-modal :size="size" id="station_edit_modal" ref="modal" :title="langTitle" :busy="loading"
             @hidden="clearContents">
        <admin-stations-form ref="form" :create-url="createUrl" :edit-url="editUrl" :is-edit-mode="isEditMode"
                             @error="close" @submitted="onSubmit">
            <template #submitButton>
                <invisible-submit-button></invisible-submit-button>
            </template>
        </admin-stations-form>

        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="disableSaveButton">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import {validationMixin} from 'vuelidate';
import ModalForm from "~/components/Common/ModalForm";
import AdminStationsForm from "~/components/Admin/Stations/StationForm";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";

export default {
    name: 'BaseEditModal',
    components: {InvisibleSubmitButton, AdminStationsForm, ModalForm},
    emits: ['relist'],
    props: {
        createUrl: String
    },
    mixins: [
        validationMixin
    ],
    data() {
        return {
            editUrl: null,
        };
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Station')
                : this.$gettext('Add Station');
        },
        isEditMode() {
            return this.editUrl !== null;
        },
        disableSaveButton() {
            return !this.$refs.form.isValid;
        }
    },
    methods: {
        create() {
            this.editUrl = null;

            this.$refs.form.reset();
            this.$refs.modal.show();
        },
        edit(recordUrl) {
            this.editUrl = recordUrl;

            this.$refs.form.reset();
            this.$refs.modal.show();
        },
        onSubmit() {
            this.$emit('relist');
            this.close();
        },
        doSubmit() {
            this.$refs.form.submit();
        },
        close() {
            this.$refs.modal.hide();
        },
        clearContents() {
            this.editUrl = null;
            this.$refs.form.clear();
        },
    }
};
</script>
