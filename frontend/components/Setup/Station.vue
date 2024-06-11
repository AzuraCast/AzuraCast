<template>
    <setup-step :step="2" />

    <section
        class="card"
        role="region"
        aria-labelledby="hdr_new_station"
    >
        <div class="card-header text-bg-primary">
            <h3
                id="hdr_new_station"
                class="card-title"
            >
                {{ $gettext('Create a New Radio Station') }}
            </h3>
        </div>

        <info-card>
            {{
                $gettext('Continue the setup process by creating your first radio station below. You can edit any of these details later.')
            }}
        </info-card>

        <div class="card-body">
            <admin-stations-form
                v-bind="$props"
                ref="$adminForm"
                :is-edit-mode="false"
                :create-url="createUrl"
                @submitted="onSubmitted"
            >
                <template #submitButtonText>
                    {{ $gettext('Create and Continue') }}
                </template>
            </admin-stations-form>
        </div>
    </section>
</template>

<script setup lang="ts">
import AdminStationsForm from "~/components/Admin/Stations/StationForm.vue";
import SetupStep from "./SetupStep.vue";
import InfoCard from "~/components/Common/InfoCard.vue";
import {onMounted, ref} from "vue";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";

const props = defineProps({
    ...stationFormProps,
    createUrl: {
        type: String,
        required: true
    },
    continueUrl: {
        type: String,
        required: true
    }
});

const $adminForm = ref<InstanceType<typeof AdminStationsForm> | null>(null);

onMounted(() => {
    $adminForm.value?.reset();
});

const onSubmitted = () => {
    window.location.href = props.continueUrl;
}
</script>
