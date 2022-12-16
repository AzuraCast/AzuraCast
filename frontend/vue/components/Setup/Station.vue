<template>
    <div>
        <setup-step :step="2"></setup-step>

        <b-card no-body>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Create a New Radio Station') }}
                </h3>
            </div>

            <info-card>
                {{ $gettext('Continue the setup process by creating your first radio station below. You can edit any of these details later.') }}
            </info-card>

            <admin-stations-form v-bind="$props" ref="form" :is-edit-mode="false" :create-url="createUrl"
                                 @submitted="onSubmitted">
                <template #submitButtonText>
                    {{ $gettext('Create and Continue') }}
                </template>
            </admin-stations-form>
        </b-card>
    </div>
</template>

<script>
import AdminStationsForm, {StationFormProps} from "~/components/Admin/Stations/StationForm";
import SetupStep from "./SetupStep";
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'StationsProfileEdit',
    components: {InfoCard, SetupStep, AdminStationsForm},
    mixins: [StationFormProps],
    props: {
        createUrl: String,
        continueUrl: {
            type: String,
            required: true
        }
    },
    mounted() {
        this.$refs.form.reset();
    },
    methods: {
        onSubmitted() {
            window.location.href = this.continueUrl;
        },
    }
}
</script>
