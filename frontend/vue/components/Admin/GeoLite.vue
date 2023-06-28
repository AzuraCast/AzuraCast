<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_install_geolite"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_install_geolite"
                class="card-title"
            >
                {{ $gettext('Install GeoLite IP Database') }}
            </h2>
        </div>

        <info-card>
            {{
                $gettext('IP Geolocation is used to guess the approximate location of your listeners based on the IP address they connect with. Use the free built-in IP Geolocation library or enter a license key on this page to use MaxMind GeoLite.')
            }}
        </info-card>

        <div class="card-body">
            <b-overlay
                variant="card"
                :show="loading"
            >
                <div class="form-row">
                    <div class="col-md-7">
                        <fieldset>
                            <legend>{{ $gettext('Instructions') }}</legend>

                            <p class="card-text">
                                {{
                                    $gettext('AzuraCast ships with a built-in free IP geolocation database. You may prefer to use the MaxMind GeoLite service instead to achieve more accurate results. Using MaxMind GeoLite requires a license key, but once the key is provided, we will automatically keep the database updated.')
                                }}
                            </p>
                            <p class="card-text">
                                {{ $gettext('To download the GeoLite database:') }}
                            </p>
                            <ul>
                                <li>
                                    {{ $gettext('Create an account on the MaxMind developer site.') }}
                                    <br>
                                    <a
                                        href="https://www.maxmind.com/en/geolite2/signup"
                                        target="_blank"
                                    >
                                        {{ $gettext('MaxMind Developer Site') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('Visit the "My License Key" page under the "Services" section.') }}
                                </li>
                                <li>
                                    {{ $gettext('Click "Generate new license key".') }}
                                </li>
                                <li>
                                    {{ $gettext('Paste the generated license key into the field on this page.') }}
                                </li>
                            </ul>
                        </fieldset>
                    </div>
                    <div class="col-md-5">
                        <fieldset class="mb-3">
                            <legend>
                                {{ $gettext('Current Installed Version') }}
                            </legend>

                            <p
                                v-if="version"
                                class="text-success card-text"
                            >
                                {{ langInstalledVersion }}
                            </p>
                            <p
                                v-else
                                class="text-danger card-text"
                            >
                                {{ $gettext('GeoLite is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <form @submit.prevent="doUpdate">
                            <fieldset>
                                <b-wrapped-form-group
                                    id="edit_form_key"
                                    :field="v$.key"
                                >
                                    <template #label>
                                        {{ $gettext('MaxMind License Key') }}
                                    </template>
                                </b-wrapped-form-group>
                            </fieldset>

                            <div class="buttons">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    {{ $gettext('Save Changes') }}
                                </button>
                                <button
                                    class="btn btn-danger"
                                    @click.prevent="doDelete"
                                >
                                    {{ $gettext('Remove Key') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </b-overlay>
        </div>
    </section>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import InfoCard from "~/components/Common/InfoCard.vue";
import {computed, onMounted, ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";

const props = defineProps({
    apiUrl: {
        type: String,
        required: true
    }
});

const loading = ref(true);
const version = ref(null);

const {form, v$} = useVuelidateOnForm(
    {
        key: {}
    },
    {
        key: null
    }
);

const {$gettext} = useTranslate();

const langInstalledVersion = computed(() => {
    return $gettext(
        'GeoLite version "%{ version }" is currently installed.',
        {
            version: version.value
        }
    );
});

const {axios} = useAxios();

const doFetch = () => {
    loading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        form.value.key = resp.data.key;
        version.value = resp.data.version;
        loading.value = false;
    });
};

onMounted(doFetch);

const {wrapWithLoading} = useNotify();

const doUpdate = () => {
    loading.value = true;
    wrapWithLoading(
        axios.post(props.apiUrl, {
            geolite_license_key: form.value.key
        })
    ).then((resp) => {
        version.value = resp.data.version;
    }).finally(() => {
        loading.value = false;
    });
};

const {confirmDelete} = useSweetAlert();

const doDelete = () => {
    confirmDelete().then((result) => {
        if (result.value) {
            form.value.key = null;
            doUpdate();
        }
    });
}
</script>
