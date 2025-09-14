<template>
    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
        <admin-settings @saved="onSaved">
            <template #preCard>
                <setup-step :step="3"/>
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
    </section>
</template>

<script setup lang="ts">
import AdminSettings from "~/components/Admin/Settings.vue";
import SetupStep from "~/components/Setup/SetupStep.vue";
import InfoCard from "~/components/Common/InfoCard.vue";

const props = defineProps<{
    continueUrl: string
}>();

const onSaved = () => {
    window.location.href = props.continueUrl;
}
</script>
