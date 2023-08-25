<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Web Hook Name')"
                :description="$gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')"
            />

            <form-group-multi-check
                v-if="triggers.length > 0"
                id="edit_form_triggers"
                class="col-md-12"
                :field="v$.triggers"
                :options="triggerOptions"
                stacked
                :label="$gettext('Web Hook Triggers')"
                :description="$gettext('This web hook will only run when the selected event(s) occur on this specific station.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {map} from "lodash";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

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

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        name: {required},
        triggers: {}
    },
    form,
    {
        name: null,
        triggers: [],
        config: {}
    }
);

const triggerOptions = map(
    props.triggers,
    (trigger) => {
        return {
            value: trigger.key,
            text: trigger.title,
            description: trigger.description
        };
    }
);
</script>
