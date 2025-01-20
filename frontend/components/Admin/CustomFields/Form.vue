<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_name"
            class="col-md-6"
            :field="v$.name"
            :label="$gettext('Field Name')"
            :description="$gettext('This will be used as the label when editing individual songs, and will show in API results.')"
        />

        <form-group-field
            id="edit_form_short_name"
            class="col-md-6"
            :field="v$.short_name"
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
            :field="v$.auto_assign"
            :label="$gettext('Automatically Set from ID3v2 Value')"
            :options="autoAssignOptions"
            :description="$gettext('Optionally select an ID3v2 metadata field that, if present, will be used to set this field\'s value.')"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {forEach} from "lodash";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {required} from "@vuelidate/validators";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab.ts";

interface CustomFieldsProps extends FormTabProps {
    autoAssignTypes: Record<string, string>
}

const props = defineProps<CustomFieldsProps>();

const emit = defineEmits<FormTabEmits>();

const {
    v$
} = useVuelidateOnFormTab(
    props,
    emit,
    {
        'name': {required},
        'short_name': {},
        'auto_assign': {}
    },
    {
        'name': '',
        'short_name': '',
        'auto_assign': ''
    }
)

const {$gettext} = useTranslate();

const autoAssignOptions = computed(() => {
    const autoAssignOptions = [
        {
            text: $gettext('Disable'),
            value: '',
        }
    ];

    forEach(props.autoAssignTypes, (typeName, typeKey) => {
        autoAssignOptions.push({
            text: typeName,
            value: typeKey
        });
    });

    return autoAssignOptions;
});
</script>
