<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <form-group-field
                id="edit_form_email"
                class="col-md-6"
                :field="r$.email"
                input-type="email"
                :label="$gettext('E-mail Address')"
            />

            <form-group-field
                id="edit_form_new_password"
                class="col-md-6"
                :field="r$.new_password"
                input-type="password"
                :label="$gettext('Password')"
            >
                <template
                    v-if="isEditMode"
                    #description
                >
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('Display Name')"
            />

            <form-group-multi-check
                id="edit_form_roles"
                class="col-md-12"
                :field="r$.roles"
                :options="roles"
                :label="$gettext('Roles')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {map} from "es-toolkit/compat";
import {computed, ref, useTemplateRef, watch} from "vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {isValidPassword, useAppRegle} from "~/vendor/regle.ts";
import {email, required, requiredIf} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

interface UsersEditModalProps extends BaseEditModalProps {
    roles: Record<number, string>
}

const props = defineProps<UsersEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

const {record: form, reset: resetFormRef} = useResettableRef(
    {
        email: '',
        new_password: '',
        name: '',
        roles: [],
    }
);

// This value is needed higher up than it's defined, so it's synced back up here.
const editMode = ref(false);

const {r$} = useAppRegle(
    form,
    {
        email: {required, email},
        new_password: {
            isValidPassword,
            required: requiredIf(() => !editMode.value)
        },
    },
    {}
);

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    form,
    props,
    emit,
    $modal,
    () => {
        resetFormRef();
        r$.$reset();
    },
    async () => (await r$.$validate()).valid,
    {
        populateForm: (data, formRef) => {
            formRef.value = mergeExisting(
                formRef.value,
                {
                    ...data,
                    roles: map(data.roles, 'id'),
                    new_password: ''
                }
            );
        },
    }
);

watch(isEditMode, (newValue) => {
    editMode.value = newValue;
});

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit User')
        : $gettext('Add User');
});

defineExpose({
    create,
    edit,
    close
});
</script>
