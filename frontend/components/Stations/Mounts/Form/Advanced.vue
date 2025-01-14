<template>
    <tab
        :label="$gettext('Advanced')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_custom_listen_url"
                class="col-md-12"
                :field="v$.custom_listen_url"
                advanced
                :label="$gettext('Mount Point URL')"
                :description="$gettext('You can set a custom URL for this stream that AzuraCast will use when referring to it on the web interface and in the Now Playing API return data. Leave empty to use the default value.')"
            />
        </div>
        <div
            v-if="isIcecast"
            class="row g-3"
        >
            <form-group-field
                id="edit_form_frontend_config"
                class="col-md-12"
                :field="v$.frontend_config"
                input-type="textarea"
                advanced
                :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}"
                :label="$gettext('Custom Frontend Configuration')"
                :description="$gettext('You can include any special mount point settings here, in either JSON { key: \'value\' } format or XML &lt;key&gt;value&lt;/key&gt;')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import {FrontendAdapter} from '~/entities/RadioAdapters';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";

interface MountAdvancedFormProps extends FormTabProps {
    stationFrontendType: FrontendAdapter
}

const props = defineProps<MountAdvancedFormProps>();
const emit = defineEmits<FormTabEmits>();

const isIcecast = computed(() => {
    return FrontendAdapter.Icecast === props.stationFrontendType;
});

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    computed(() => {
        const validations: {
            [key: string | number]: any
        } = {
            custom_listen_url: {}
        };

        if (isIcecast.value) {
            validations.frontend_config = {};
        }

        return validations;
    }),
    () => {
        const blankForm: {
            [key: string | number]: any
        } = {
            custom_listen_url: null,
        };

        if (isIcecast.value) {
            blankForm.frontend_config = null;
        }

        return blankForm;
    }
);
</script>
