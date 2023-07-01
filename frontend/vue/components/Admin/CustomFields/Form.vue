<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-6"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Field Name') }}
                </template>
                <template #description>
                    {{
                        $gettext('This will be used as the label when editing individual songs, and will show in API results.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_short_name"
                class="col-md-6"
                :field="form.short_name"
            >
                <template #label>
                    {{ $gettext('Programmatic Name') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally specify an API-friendly name, such as "field_name". Leave this field blank to automatically create one based on the name.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_auto_assign"
                class="col-md-6"
                :field="form.auto_assign"
            >
                <template #label>
                    {{ $gettext('Automatically Set from ID3v2 Value') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally select an ID3v2 metadata field that, if present, will be used to set this field\'s value.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-select
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :options="autoAssignOptions"
                    />
                </template>
            </form-group-field>
        </div>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {forEach} from "lodash";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    autoAssignTypes: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const autoAssignOptions = computed(() => {
    let autoAssignOptions = [
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
