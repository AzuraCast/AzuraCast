<template>
    <div class="row g-3">
        <form-group-field
            id="form_edit_name"
            class="col-md-12"
            :field="form.name"
            :label="$gettext('Web Hook Name')"
            :description="$gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')"
        />

        <form-group-multi-check
            v-if="triggers.length > 0"
            id="edit_form_triggers"
            class="col-md-12"
            :field="form.triggers"
            :options="triggerOptions"
            stacked
            :label="$gettext('Web Hook Triggers')"
            :description="$gettext('This web hook will only run when the selected event(s) occur on this specific station.')"
        >
            <template
                v-for="(trigger) in triggers"
                :key="trigger.key"
                #[getSlotName(trigger)]
            >
                <h6 class="font-weight-bold mb-0">
                    {{ trigger.title }}
                </h6>
                <p class="card-text small">
                    {{ trigger.description }}
                </p>
            </template>
        </form-group-multi-check>
    </div>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {map} from "lodash";

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

const getSlotName = (trigger) => 'label(' + trigger.key + ')';

const triggerOptions = map(
    props.triggers,
    (trigger) => {
        return {
            value: trigger.key,
            text: trigger.title
        };
    }
);
</script>
