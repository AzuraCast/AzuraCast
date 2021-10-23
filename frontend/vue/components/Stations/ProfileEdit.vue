<template>
    <section class="card" role="region">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                <translate key="lang_hdr_profile_edit">Edit Station Profile</translate>
            </h3>
        </div>

        <admin-stations-form ref="form" v-bind="$props" :is-edit-mode="true" :edit-url="editUrl"
                             @submitted="onSubmitted"></admin-stations-form>
    </section>
</template>

<script>
import AdminStationsForm, {StationFormProps} from "~/components/Admin/Stations/StationForm";

export default {
    name: 'StationsProfileEdit',
    components: {AdminStationsForm},
    mixins: [StationFormProps],
    props: {
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
