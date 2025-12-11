<template>
    <card-page
        header-id="hdr_install_shoutcast"
        :title="$gettext('Install Shoutcast 2 DNAS')"
    >
        <div class="card-body">
            <loading :loading="isLoading">
                <div class="row g-3">
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                {{ $gettext('Instructions') }}
                            </legend>

                            <p class="card-text">
                                {{
                                    $gettext('Shoutcast 2 DNAS is not free software, and its restrictive license does not allow AzuraCast to distribute the Shoutcast binary.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install Shoutcast:') }}
                            </p>

                            <ul>
                                <li>
                                    {{ $gettext('Download the Linux x64 binary from the Shoutcast Radio Manager:') }}
                                    <br>
                                    <a
                                        href="https://radiomanager.shoutcast.com/register/serverSoftwareFreemium"
                                        target="_blank"
                                    >
                                        {{ $gettext('Shoutcast Radio Manager') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('The file name should look like:') }}
                                    <br>
                                    <code>sc_serv2_linux_x64-latest.tar.gz</code>
                                </li>
                                <li>
                                    {{
                                        $gettext('Upload the file on this page to automatically extract it into the proper directory.')
                                    }}
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
                                v-if="record.version"
                                class="text-success card-text"
                            >
                                {{ langInstalledVersion }}
                            </p>
                            <p
                                v-else
                                class="text-danger card-text"
                            >
                                {{ $gettext('Shoutcast 2 DNAS is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <flow-upload
                            :target-url="apiUrl"
                            :valid-mime-types="['.tar.gz']"
                            @complete="relist"
                        />

                        <div v-if="record.version" class="block-buttons buttons mt-3">
                            <button
                                type="button"
                                class="btn btn-danger"
                                @click="doDelete"
                            >
                                {{ $gettext('Uninstall') }}
                            </button>
                        </div>
                    </div>
                </div>
            </loading>
        </div>
    </card-page>
</template>

<script setup lang="ts">
import FlowUpload from "~/components/Common/FlowUpload.vue";
import {computed, onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {ApiAdminShoutcastStatus} from "~/entities/ApiInterfaces.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";
import {useDialog} from "~/components/Common/Dialogs/useDialog.ts";

const {getApiUrl} = useApiRouter();
const apiUrl = getApiUrl('/admin/shoutcast');

type Row = ApiAdminShoutcastStatus;

const isLoading = ref(true);
const record = ref<Row>({
    version: null
});

const {$gettext} = useTranslate();

const langInstalledVersion = computed(() => {
    return $gettext(
        'Shoutcast version "%{version}" is currently installed.',
        {
            version: record.value.version ?? 'N/A'
        }
    );
});

const {axios} = useAxios();

const relist = async () => {
    isLoading.value = true;

    const {data} = await axios.get<Row>(apiUrl.value);
    record.value = data;
    isLoading.value = false;
};

onMounted(relist);

const {confirmDelete} = useDialog();

const doDelete = async () => {
    const {value} = await confirmDelete({
        title: $gettext('Remove Shoutcast 2 DNAS?'),
        confirmButtonText: $gettext('Uninstall')
    });

    if (!value) {
        return;
    }

    await axios.delete<Row>(apiUrl.value);
    await relist();
}
</script>
