<template>
    <tab
        :label="$gettext('Advanced')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_custom_listen_url"
                class="col-md-12"
                :field="r$.custom_listen_url"
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
                :field="r$.frontend_config"
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
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";
import {FrontendAdapters} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useStationsMountsForm} from "~/components/Stations/Mounts/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";

const props = defineProps<{
    stationFrontendType: FrontendAdapters
}>();

const isIcecast = computed(() => {
    return FrontendAdapters.Icecast === props.stationFrontendType;
});

const {r$} = storeToRefs(useStationsMountsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.advancedTab));
</script>
