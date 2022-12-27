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

<script setup>
import AdminStationsForm from "~/components/Admin/Stations/StationForm";
import {onMounted, ref} from "vue";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";

const props = defineProps({
    ...stationFormProps,
    editUrl: String,
    continueUrl: {
        type: String,
        required: true
    }
});

const form = ref(); // AdminStationsForm

onMounted(() => {
    form.value?.reset();
});

const onSubmitted = () => {
    window.location.href = props.continueUrl;
}
</script>
