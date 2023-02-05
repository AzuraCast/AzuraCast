<template>
    <section
        class="card"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                {{ $gettext('Edit Station Profile') }}
            </h3>
        </div>

        <error-card v-if="error != null">
            <p class="card-text">
                {{ $gettext('An error occurred while loading the station profile:') }}
            </p>

            <p class="card-text">
                {{ error }}
            </p>

            <p class="card-text">
                {{ $gettext('Click the button below to retry loading the page.') }}
            </p>

            <a
                class="btn btn-lg btn-light"
                @click="retry"
            >
                <icon icon="refresh" />
                {{ $gettext('Reload') }}
            </a>
        </error-card>
        <admin-stations-form
            v-else
            v-bind="pickProps(props, stationFormProps)"
            ref="$form"
            is-edit-mode
            :edit-url="editUrl"
            @submitted="onSubmitted"
            @error="onError"
        />
    </section>
</template>

<script setup>
import AdminStationsForm from "~/components/Admin/Stations/StationForm";
import {nextTick, onMounted, ref} from "vue";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";
import {pickProps} from "~/functions/pickProps";
import Icon from "~/components/InlinePlayer.vue";
import ErrorCard from "~/components/Common/ErrorCard.vue";

const props = defineProps({
    ...stationFormProps,
    editUrl: {
        type: String,
        required: true
    },
    continueUrl: {
        type: String,
        required: true
    }
});

const $form = ref(); // AdminStationsForm

onMounted(() => {
    $form.value?.reset();
});

const error = ref(null);

const retry = () => {
    error.value = null;

    nextTick(() => {
        $form.value?.reset();
    });
}

const onError = (err) => {
    error.value = err;
}

const onSubmitted = () => {
    window.location.href = props.continueUrl;
}
</script>
