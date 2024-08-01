<template>
    <tab
        :label="$gettext('Branding')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="edit_form_public_custom_html"
                class="col-md-12"
                :field="v$.branding_config.public_custom_html"
                :label="$gettext('Custom HTML for Public Pages')"
            >
                <template #default="slotProps">
                    <codemirror-textarea
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        mode="html"
                    />
                </template>
            </form-group-field>
        </div>
    </tab>
</template>

<script setup lang="ts">
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        branding_config: {
            public_custom_html: {}
        },
    },
    form,
    {
        branding_config: {
            public_custom_html: ''
        }
    }
);
</script>
