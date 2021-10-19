<template>
    <admin-settings :api-url="apiUrl" :release-channel="releaseChannel" @saved="onSaved">
        <template #preCard>
            <setup-step :step="3"></setup-step>
        </template>
        <template #cardTitle>
            <translate key="lang_setup_settings_hdr">Customize AzuraCast Settings</translate>
        </template>
        <template #cardUpper>
            <info-card>
                <translate key="lang_setup_settings_info">Complete the setup process by providing some information about your broadcast environment. These settings can be changed later from the administration panel.</translate>
            </info-card>
        </template>
        <template #submitButtonName>
            <translate key="lang_setup_settings_btn">Save and Continue</translate>
        </template>
    </admin-settings>
</template>
<script>
import AdminSettings from "~/components/Admin/Settings";
import SetupStep from "./SetupStep";
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'SetupSettings',
    components: {InfoCard, SetupStep, AdminSettings},
    props: {
        apiUrl: String,
        releaseChannel: {
            type: String,
            default: 'rolling',
            required: false
        },
        continueUrl: {
            type: String,
            required: true
        }
    },
    methods: {
        onSaved() {
            window.location.href = this.continueUrl;
        }
    }
}
</script>
