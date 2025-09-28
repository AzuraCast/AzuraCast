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
                id="edit_form_username"
                class="col-md-6"
                :field="r$.username"
            >
                <template #label>
                    {{ $gettext('Username') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_password"
                class="col-md-6"
                :field="r$.password"
                input-type="password"
            >
                <template
                    v-if="isEditMode"
                    #label
                >
                    {{ $gettext('New Password') }}
                </template>
                <template
                    v-else
                    #label
                >
                    {{ $gettext('Password') }}
                </template>

                <template
                    v-if="isEditMode"
                    #description
                >
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_publicKeys"
                class="col-md-12"
                :field="r$.publicKeys"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('SSH Public Keys') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.')
                    }}
                </template>
            </form-group-field>
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref, toRef, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useAppRegle} from "~/vendor/regle.ts";
import {required, requiredIf} from "@regle/rules";
import {SftpUser} from "~/entities/ApiInterfaces.ts";
import mergeExisting from "~/functions/mergeExisting.ts";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

type SftpUsersRecord = Required<
    Omit<SftpUser, 'id'>
>

const form = ref<SftpUsersRecord>({
    username: '',
    password: '',
    publicKeys: null
});

// This value is needed higher up than it's defined, so it's synced back up here.
const editMode = ref(false);

const {r$} = useAppRegle(
    form,
    {
        username: {required},
        password: {
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
} = useBaseEditModal<SftpUsersRecord>(
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
            toState: mergeExisting(r$.$value, data)
        })
    },
    async () => {
        const {valid} = await r$.$validate();
        return {valid, data: form.value};
    }
);

watch(isEditMode, (newValue) => {
    editMode.value = newValue;
});

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit SFTP User')
        : $gettext('Add SFTP User');
});

defineExpose({
    create,
    edit,
    close
});
</script>
