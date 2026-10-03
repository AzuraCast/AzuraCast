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
                v-model="linkMode"
                class="col-md-6"
                :label="$gettext('Linked Media File Tag')"
                :options="autoAssignOptions"
                :description="$gettext('Optionally link this field to a tag in the media file. The field is filled from that tag when the file is imported and its value is written back to the tag when the file is saved.')"
            />

            <form-group-field
                v-if="isCustomTag"
                id="edit_form_auto_assign_custom"
                class="col-md-6"
                :field="r$.auto_assign"
                input-trim
                :input-attrs="{ maxlength: 100 }"
                :label="$gettext('Custom Tag Name')"
                :description="$gettext('The tag name to use. It is written as an ID3v2 TXXX frame in MP3 files and as a Vorbis comment in FLAC and Ogg files. Only printable ASCII characters except equals sign are allowed.')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {
    maxLength,
    regex,
    required,
    requiredIf,
    withMessage,
} from "@regle/rules";
import { computed, ref, toRef, useTemplateRef } from "vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import { CustomField } from "~/entities/ApiInterfaces.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    useBaseEditModal,
} from "~/functions/useBaseEditModal";
import { useTranslate } from "~/vendor/gettext";
import { useAppRegle } from "~/vendor/regle.ts";

const props = defineProps<
    BaseEditModalProps & {
        autoAssignTypes: Record<string, string>;
    }
>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef("$modal");

const { $gettext } = useTranslate();

const CUSTOM_TAG_OPTION = "__custom__";
const CUSTOM_TAG_REGEX = /^[ -<>-}]+$/;

type Form = Required<Omit<CustomField, "id">>;

const form = ref<Form>({
    name: "",
    short_name: "",
    auto_assign: "",
});

const isKnownTag = (value: string | null | undefined): value is string => {
    return !!value && value in props.autoAssignTypes;
};

const hasChosenCustomTagOption = ref(false);

const linkMode = computed<string>({
    get: () => {
        const value = form.value.auto_assign ?? "";
        if (isKnownTag(value)) {
            return value;
        }

        return value !== "" || hasChosenCustomTagOption.value
            ? CUSTOM_TAG_OPTION
            : "";
    },
    set: (newMode) => {
        if (newMode === CUSTOM_TAG_OPTION) {
            hasChosenCustomTagOption.value = true;
            if (isKnownTag(form.value.auto_assign)) {
                form.value.auto_assign = "";
            }
            return;
        }

        hasChosenCustomTagOption.value = false;
        form.value.auto_assign = newMode;
    },
});

const isCustomTag = computed(() => linkMode.value === CUSTOM_TAG_OPTION);

const { r$ } = useAppRegle(
    form,
    {
        name: { required },
        auto_assign: {
            required: requiredIf(() => isCustomTag.value),
            maxLength: maxLength(100),
            regex: withMessage(
                regex(CUSTOM_TAG_REGEX),
                $gettext(
                    "This field may only contain printable ASCII characters except equals sign.",
                ),
            ),
        },
    },
    {},
);

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close,
} = useBaseEditModal<Form>(
    toRef(props, "createUrl"),
    emit,
    $modal,
    () => {
        r$.$reset({
            toOriginalState: true,
        });
        hasChosenCustomTagOption.value = false;
    },
    (data) => {
        r$.$reset({
            toState: mergeExisting(r$.$value, data),
        });
        hasChosenCustomTagOption.value =
            !!data.auto_assign && !isKnownTag(data.auto_assign);
    },
    async () => {
        const { valid } = await r$.$validate();
        return {
            valid,
            data: {
                ...form.value,
                auto_assign: form.value.auto_assign ?? "",
            },
        };
    },
);

const autoAssignOptions = computed(() => {
    const autoAssignOptions = [
        {
            text: $gettext("Disable"),
            value: "",
        },
    ];

    for (const typeKey in props.autoAssignTypes) {
        autoAssignOptions.push({
            text: props.autoAssignTypes[typeKey],
            value: typeKey,
        });
    }

    autoAssignOptions.push({
        text: $gettext("Custom Tag Name"),
        value: CUSTOM_TAG_OPTION,
    });

    return autoAssignOptions;
});

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext("Edit Custom Field")
        : $gettext("Add Custom Field");
});

defineExpose({
    create,
    edit,
    close,
});
</script>
