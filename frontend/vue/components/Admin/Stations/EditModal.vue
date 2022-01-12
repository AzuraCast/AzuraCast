<template>
    <b-modal size="lg" id="station_edit_modal" ref="modal" :title="langTitle" :busy="loading"
             @shown="resetForm" @hidden="clearContents">
        <admin-stations-form ref="form" v-bind="$props" is-modal :create-url="createUrl" :edit-url="editUrl"
                             :is-edit-mode="isEditMode" @error="close" @submitted="onSubmit"
                             @validUpdate="onValidUpdate" @loadingUpdate="onLoadingUpdate">
            <template #submitButton>
                <invisible-submit-button></invisible-submit-button>
            </template>
        </admin-stations-form>

        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button :variant="(disableSaveButton) ? 'danger' : 'primary'" type="submit" @click="doSubmit">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import ModalForm from "~/components/Common/ModalForm";
import AdminStationsForm, {StationFormProps} from "~/components/Admin/Stations/StationForm";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";

export default {
    name: 'AdminStationsEditModal',
    inheritAttrs: false,
    components: {InvisibleSubmitButton, AdminStationsForm, ModalForm},
    emits: ['relist'],
    props: {
        createUrl: String
    },
    mixins: [
        StationFormProps
    ],
    data() {
        return {
            editUrl: null,
            loading: true,
            disableSaveButton: true,
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
        }
    },
    methods: {
        onValidUpdate(newValue) {
            this.disableSaveButton = !newValue;
        },
        onLoadingUpdate(newValue) {
            this.loading = newValue;
        },
        create() {
            this.editUrl = null;
            this.$refs.modal.show();
        },
        edit(recordUrl) {
            this.editUrl = recordUrl;
            this.$refs.modal.show();
        },
        resetForm() {
            this.$refs.form.reset();
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
        },
    }
};
</script>
