<template>
    <section class="card">
        <div class="card-header text-bg-primary">
            <h2 class="card-title mb-0">
                {{ $gettext('Services') }}
            </h2>
        </div>

        <table class="table table-striped table-responsive mb-0">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 75%;">
                <col style="width: 20%;">
            </colgroup>
            <tbody>
                <tr
                    v-for="service in services"
                    :key="service.name"
                    class="align-middle"
                >
                    <td class="text-center pe-2">
                        <running-badge :running="service.running" />
                    </td>
                    <td class="ps-2">
                        <h6 class="mb-0">
                            {{ service.name }}<br>
                            <small>{{ service.description }}</small>
                        </h6>
                    </td>
                    <td>
                        <button
                            v-if="service.links.restart"
                            type="button"
                            class="btn btn-sm"
                            :class="service.running ? 'btn-primary' : 'btn-danger'"
                            @click="doRestart(service.links.restart)"
                        >
                            {{ $gettext('Restart') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {getApiUrl} from "~/router.ts";
import {onMounted, onScopeDispose, ref} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import {useNotify} from "~/functions/useNotify";

const servicesUrl = getApiUrl('/admin/services');

const services = ref([]);

const {axios} = useAxios();

let serviceTimeout = null;

const clearUpdates = () => {
    if (serviceTimeout) {
        clearTimeout(serviceTimeout);
        serviceTimeout = null;
    }
};

onScopeDispose(clearUpdates);

const updateServices = () => {
    axios.get(servicesUrl.value).then((response) => {
        services.value = response.data;

        clearUpdates();
        serviceTimeout = setTimeout(updateServices, (!document.hidden) ? 5000 : 15000);
    }).catch((error) => {
        if (!error.response || error.response.data.code !== 403) {
            clearUpdates();
            serviceTimeout = setTimeout(updateServices, (!document.hidden) ? 15000 : 30000);
        }
    });
};

onMounted(updateServices);

const {notifySuccess} = useNotify();

const doRestart = (serviceUrl) => {
    axios.post(serviceUrl).then((resp) => {
        notifySuccess(resp.data.message);
    });
};
</script>
