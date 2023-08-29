<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="v$.config.token"
                :label="$gettext('API Token')"
                :description="$gettext('This can be retrieved from the GetMeRadio dashboard.')"
            />

            <form-group-field
                id="form_config_station_id"
                class="col-md-6"
                :field="v$.config.station_id"
                :label="$gettext('GetMeRadio Station ID')"
                :description="$gettext('This is a 3-5 digit number.')"
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
            token: {required},
            station_id: {required}
        }
    },
    form,
    {
        config: {
            token: '',
            station_id: '',
        }
    }
);
</script>
