<template>
    <admin-stations-form v-bind="$props" :is-edit-mode="true" :edit-url="editUrl"
                         @submitted.prevent="onSubmitted"></admin-stations-form>
</template>
<script>
import AdminStationsForm, {StationFormProps} from "~/components/Admin/Stations/StationForm";

export default {
    name: 'StationsEditProfile',
    components: {AdminStationsForm},
    mixins: [StationFormProps],
    props: {
        editUrl: String,
        continueUrl: {
            type: String,
            required: true
        }
    },
    methods: {
        onSubmitted() {
            window.location.href = this.continueUrl;
        },
    }
}
</script>
