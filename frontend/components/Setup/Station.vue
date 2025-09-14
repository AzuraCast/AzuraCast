<template>
    <setup-step :step="2"/>

    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
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
                    v-bind="formProps"
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
    </section>
</template>

<script setup lang="ts">
import AdminStationsForm from "~/components/Admin/Stations/StationForm.vue";
import SetupStep from "~/components/Setup/SetupStep.vue";
import InfoCard from "~/components/Common/InfoCard.vue";
import {ApiAdminVueStationsFormProps} from "~/entities/ApiInterfaces.ts";

interface SetupStationProps {
    formProps: ApiAdminVueStationsFormProps,
    createUrl: string,
    continueUrl: string,
}

const props = defineProps<SetupStationProps>();

const onSubmitted = () => {
    window.location.href = props.continueUrl;
}
</script>
