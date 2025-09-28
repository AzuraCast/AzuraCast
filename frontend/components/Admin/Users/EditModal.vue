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
import {computed, ref, toRef, useTemplateRef, watch} from "vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import mergeExisting from "~/functions/mergeExisting.ts";
import {isValidPassword, useAppRegle} from "~/vendor/regle.ts";
import {email, required, requiredIf} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps<BaseEditModalProps & {
    roles: Record<number, string>
}>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

// This value is needed higher up than it's defined, so it's synced back up here.
const editMode = ref(false);

const {r$} = useAppRegle(
    {
        email: '',
        new_password: '',
        name: '',
        roles: [],
    },
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
    toRef(props, 'createUrl'),
    emit,
    $modal,
    () => {
        r$.$reset({
            toOriginalState: true
        });
    },
    (data) => {
        r$.$reset({
            toState: mergeExisting(r$.$value, {
                ...data,
                roles: map(data.roles, 'id'),
                new_password: ''
            })
        })
    },
    async () => {
        const {valid, data} = await r$.$validate();
        return {valid, data};
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
