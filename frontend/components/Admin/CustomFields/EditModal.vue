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
                id="edit_form_name"
                class="col-md-6"
                :field="r$.name"
                :label="$gettext('Field Name')"
                :description="$gettext('This will be used as the label when editing individual songs, and will show in API results.')"
            />

            <form-group-field
                id="edit_form_short_name"
                class="col-md-6"
                :field="r$.short_name"
                :label="$gettext('Programmatic Name')"
            >
                <template #description>
                    {{
                        $gettext('Optionally specify an API-friendly name, such as "field_name". Leave this field blank to automatically create one based on the name.')
                    }}
                </template>
            </form-group-field>

            <form-group-select
                id="edit_form_auto_assign"
                class="col-md-6"
                :field="r$.auto_assign"
                :label="$gettext('Automatically Set from ID3v2 Value')"
                :options="autoAssignOptions"
                :description="$gettext('Optionally select an ID3v2 metadata field that, if present, will be used to set this field\'s value.')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, ref, toRef, useTemplateRef} from "vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import {CustomField} from "~/entities/ApiInterfaces.ts";
import {required} from "@regle/rules";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useAppRegle} from "~/vendor/regle.ts";
import mergeExisting from "~/functions/mergeExisting.ts";

const props = defineProps<BaseEditModalProps & {
    autoAssignTypes: Record<string, string>
}>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal')

type Form = Required<Omit<CustomField, 'id'>>;

const form = ref<Form>({
    name: '',
    short_name: '',
    auto_assign: ''
});

const {r$} = useAppRegle(
    form,
    {
        name: {required},
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
} = useBaseEditModal<Form>(
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

const {$gettext} = useTranslate();

const autoAssignOptions = computed(() => {
    const autoAssignOptions = [
        {
            text: $gettext('Disable'),
            value: '',
        }
    ];

    for (const typeKey in props.autoAssignTypes) {
        autoAssignOptions.push({
            text: props.autoAssignTypes[typeKey],
            value: typeKey
        });
    }

    return autoAssignOptions;
});

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Custom Field')
        : $gettext('Add Custom Field');
});

defineExpose({
    create,
    edit,
    close
});
</script>
