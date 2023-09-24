<template>
    <admin-settings
        v-bind="props"
        @saved="onSaved"
    >
        <template #preCard>
            <setup-step :step="3" />
        </template>
        <template #cardTitle>
            {{ $gettext('Customize AzuraCast Settings') }}
        </template>
        <template #cardUpper>
            <info-card>
                {{
                    $gettext('Complete the setup process by providing some information about your broadcast environment. These settings can be changed later from the administration panel.')
                }}
            </info-card>
        </template>
        <template #submitButtonName>
            {{ $gettext('Save and Continue') }}
        </template>
    </admin-settings>
</template>

<script setup lang="ts">
import AdminSettings from "~/components/Admin/Settings.vue";
import SetupStep from "./SetupStep.vue";
import InfoCard from "~/components/Common/InfoCard.vue";
import settingsProps from "~/components/Admin/settingsProps";

const props = defineProps({
    ...settingsProps,
    continueUrl: {
        type: String,
        required: true
    }
});

const onSaved = () => {
    window.location.href = props.continueUrl;
}
</script>
