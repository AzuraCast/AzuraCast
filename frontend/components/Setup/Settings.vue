<template>
    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
        <admin-settings :loading="loading" @saved="onSaved">
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
import {delay} from "es-toolkit";
import {ref} from "vue";

const props = defineProps<{
    continueUrl: string
}>();

const loading = ref(false);

const onSaved = async () => {
    loading.value = true;
    
    await delay(2000);

    window.location.href = props.continueUrl;
}
</script>
