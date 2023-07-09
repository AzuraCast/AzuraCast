<template>
    <!-- TODO Fix property injection here to match other settings forms -->
    <admin-settings
        :api-url="apiUrl"
        :release-channel="releaseChannel"
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

<script setup>
import AdminSettings from "~/components/Admin/Settings";
import SetupStep from "./SetupStep";
import InfoCard from "~/components/Common/InfoCard";

const props = defineProps({
    apiUrl: {
        type: String,
        required: true
    },
    releaseChannel: {
        type: String,
        default: 'rolling',
        required: false
    },
    continueUrl: {
        type: String,
        required: true
    }
});

const onSaved = () => {
    window.location.href = props.continueUrl;
}
</script>
