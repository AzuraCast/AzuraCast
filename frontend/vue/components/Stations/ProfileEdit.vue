<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_edit_profile"
    >
        <div class="card-header text-bg-primary">
            <h3
                id="hdr_edit_profile"
                class="card-title"
            >
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

            <button
                type="button"
                class="btn btn-light"
                @click="retry"
            >
                <icon icon="refresh" />
                <span>
                    {{ $gettext('Reload') }}
                </span>
            </button>
        </error-card>
        <div
            v-else
            class="card-body"
        >
            <admin-stations-form
                v-bind="pickProps(props, stationFormProps)"
                ref="$form"
                is-edit-mode
                :edit-url="editUrl"
                @submitted="onSubmitted"
                @error="onError"
            />
        </div>
    </section>
</template>

<script setup>
import AdminStationsForm from "~/components/Admin/Stations/StationForm";
import {nextTick, onMounted, ref} from "vue";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";
import {pickProps} from "~/functions/pickProps";
import Icon from "~/components/InlinePlayer.vue";
import ErrorCard from "~/components/Common/ErrorCard.vue";
import {getStationApiUrl} from "~/router";
import {useRouter} from "vue-router";

const props = defineProps(stationFormProps);

const editUrl = getStationApiUrl('/profile/edit');

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

const router = useRouter();

const onSubmitted = async () => {
    await router.push({
        name: 'stations:index'
    });
    
    window.location.reload();
}
</script>
