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
                <template #default="{id, model}">
                    <codemirror-textarea
                        :id="id"
                        v-model="model.$model"
                        mode="html"
                    />
                </template>
            </form-group-field>
        </div>
    </tab>
</template>

<script setup lang="ts">
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {GenericForm} from "~/entities/Forms.ts";

const form = defineModel<GenericForm>('form', {required: true});

const {v$, tabClass} = useVuelidateOnFormTab(
    form,
    {
        branding_config: {
            public_custom_html: {}
        },
    },
    () => ({
        branding_config: {
            public_custom_html: ''
        }
    })
);
</script>
