<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_api_secret"
                class="col-md-6"
                :field="v$.config.api_secret"
                :label="$gettext('Measurement Protocol API Secret')"
                :description="$gettext('This can be generated in the &quot;Events&quot; section for a measurement.')"
            />

            <form-group-field
                id="form_config_measurement_id"
                class="col-md-6"
                :field="v$.config.measurement_id"
                :label="$gettext('Measurement ID')"
                :description="$gettext('A unique identifier (i.e. &quot;G-A1B2C3D4&quot;) for this measurement stream.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        config: {
            api_secret: {required},
            measurement_id: {required}
        }
    },
    form,
    {
        config: {
            api_secret: '',
            measurement_id: ''
        }
    }
);
</script>
