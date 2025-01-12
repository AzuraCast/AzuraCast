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
import AdminSettings, {SettingsProps} from "~/components/Admin/Settings.vue";
import SetupStep from "./SetupStep.vue";
import InfoCard from "~/components/Common/InfoCard.vue";

interface SetupSettingsProps extends SettingsProps {
    continueUrl: string,
}

const props = defineProps<SetupSettingsProps>();

const onSaved = () => {
    window.location.href = props.continueUrl;
}
</script>
