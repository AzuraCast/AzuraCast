<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Web Hook Name') }}
                </template>
                <template #description>
                    {{
                        $gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                v-if="triggers.length > 0"
                id="edit_form_triggers"
                class="col-md-12"
                :field="form.triggers"
            >
                <template #label>
                    {{ $gettext('Web Hook Triggers') }}
                </template>
                <template #description>
                    {{
                        $gettext('This web hook will only run when the selected event(s) occur on this specific station.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-checkbox-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                    >
                        <b-form-checkbox
                            v-for="(trigger) in triggers"
                            :key="trigger.key"
                            :value="trigger.key"
                        >
                            <h6 class="font-weight-bold mb-0">
                                {{ trigger.title }}
                            </h6>
                            <p class="card-text small">
                                {{ trigger.description }}
                            </p>
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </template>
            </form-group-field>
        </div>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    triggers: {
        type: Array,
        required: true
    }
});
</script>
