<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Name')"
            >
                <template #description>
                    {{
                        $gettext('A name for this simulcasting stream to help you identify it.')
                    }}
                </template>
            </form-group-field>

            <form-group-multi-check
                id="edit_form_adapter"
                class="col-md-6"
                :field="v$.adapter"
                :options="adapterOptions"
                stacked
                radio
                :label="$gettext('Platform')"
            />

            <form-group-field
                id="edit_form_stream_key"
                class="col-md-6"
                :field="v$.stream_key"
                :label="$gettext('Stream Key')"
            >
                <template #description>
                    {{
                        $gettext('The stream key provided by the platform.')
                    }}
                </template>
            </form-group-field>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required, minLength, maxLength} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

const form = defineModel<ApiGenericForm>('form', {required: true});

const {v$, tabClass} = useVuelidateOnFormTab(
    form,
    {
        name: {required, minLength: minLength(1), maxLength: maxLength(255)},
        adapter: {required},
        stream_key: {required, minLength: minLength(1), maxLength: maxLength(500)}
    },
    {
        name: '',
        adapter: '',
        stream_key: ''
    }
);

const adapterOptions = [
    {
        value: 'facebook',
        text: 'Facebook Live',
    },
    {
        value: 'youtube',
        text: 'YouTube Live'
    }
];
</script>
