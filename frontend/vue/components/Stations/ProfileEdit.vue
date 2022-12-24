<template>
    <section class="card" role="region">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                {{ $gettext('Edit Station Profile') }}
            </h3>
        </div>

        <admin-stations-form v-bind="$props" ref="form" :is-edit-mode="true" :edit-url="editUrl"
                             @submitted="onSubmitted"></admin-stations-form>
    </section>
</template>

<script>
import AdminStationsForm, {stationFormProps} from "~/components/Admin/Stations/StationForm";

export default {
    name: 'StationsProfileEdit',
    components: {AdminStationsForm},
    props: {
        ...stationFormProps,
        editUrl: String,
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
