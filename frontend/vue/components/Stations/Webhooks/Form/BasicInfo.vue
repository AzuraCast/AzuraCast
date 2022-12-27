<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_edit_name" :field="form.name">
                <template #label>
                    {{ $gettext('Web Hook Name') }}
                </template>
                <template #description>
                    {{
                        $gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group v-if="triggerOptions.length > 0" class="col-md-12"
                                  id="edit_form_triggers"
                                  :field="form.triggers">
                <template #label>
                    {{ $gettext('Web Hook Triggers') }}
                </template>
                <template #description>
                    {{
                        $gettext('This web hook will only run when the selected event(s) occur on this specific station.')
                    }}
                </template>
                <template #default="props">
                    <b-form-checkbox-group :id="props.id" :options="triggerOptions"
                                           v-model="props.field.$model" stacked>
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: Object,
    triggerOptions: Array
});
</script>
