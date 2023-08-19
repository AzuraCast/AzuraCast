<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_matomo_url"
                class="col-md-12"
                :field="v$.config.matomo_url"
                input-type="url"
                :label="$gettext('Matomo Installation Base URL')"
                :description="$gettext('The full base URL of your Matomo installation.')"
            />

            <form-group-field
                id="form_config_site_id"
                class="col-md-6"
                :field="v$.config.site_id"
                :label="$gettext('Matomo Site ID')"
                :description="$gettext('The numeric site ID for this site.')"
            />

            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="v$.config.token"
                :label="$gettext('Matomo API Token')"
                :description="$gettext('Optionally supply an API token to allow IP address overriding.')"
            />
        </div>
    </tab>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
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
            matomo_url: {required},
            site_id: {required},
            token: {},
        }
    },
    form,
    {
        config: {
            matomo_url: '',
            site_id: '',
            token: ''
        }
    }
);
</script>
