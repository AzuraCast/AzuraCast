<template>
    <card-page
        header-id="hdr_install_geolite"
        :title="$gettext('Install GeoLite IP Database')"
    >
        <template #info>
            {{
                $gettext('IP Geolocation is used to guess the approximate location of your listeners based on the IP address they connect with. Use the free built-in IP Geolocation library or enter a license key on this page to use MaxMind GeoLite.')
            }}
        </template>

        <div class="card-body">
            <loading :loading="isLoading">
                <div class="row g-3">
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
                                <form-group-field
                                    id="edit_form_key"
                                    :field="v$.key"
                                >
                                    <template #label>
                                        {{ $gettext('MaxMind License Key') }}
                                    </template>
                                </form-group-field>
                            </fieldset>

                            <div class="buttons">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    {{ $gettext('Save Changes') }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    @click="doDelete"
                                >
                                    {{ $gettext('Remove Key') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </loading>
        </div>
    </card-page>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed, onMounted, ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";

const apiUrl = getApiUrl('/admin/geolite');

const isLoading = ref(true);
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
    isLoading.value = true;

    axios.get(apiUrl.value).then((resp) => {
        form.value.key = resp.data.key;
        version.value = resp.data.version;
        isLoading.value = false;
    });
};

onMounted(doFetch);

const {wrapWithLoading} = useNotify();

const doUpdate = () => {
    isLoading.value = true;
    wrapWithLoading(
        axios.post(apiUrl.value, {
            geolite_license_key: form.value.key
        })
    ).then((resp) => {
        version.value = resp.data.version;
    }).finally(() => {
        isLoading.value = false;
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
