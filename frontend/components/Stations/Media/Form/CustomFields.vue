<template>
    <tab :label="$gettext('Custom Fields')">
        <div class="row">
            <form-group-field
                v-for="field in customFields"
                :id="'edit_form_custom_'+field.short_name"
                :key="field.short_name"
                class="col-md-6"
                :field="r$.custom_fields[field.short_name]"
                :label="field.name"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {CustomField} from "~/entities/ApiInterfaces.ts";
import Tab from "~/components/Common/Tab.vue";
import {storeToRefs} from "pinia";
import {useStationsMediaForm} from "~/components/Stations/Media/Form/form.ts";

defineProps<{
    customFields: CustomField[],
}>();

const {r$} = storeToRefs(useStationsMediaForm());
</script>
