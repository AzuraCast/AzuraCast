<template>
    <section class="card">
        <div class="card-header text-bg-primary">
            <h2 class="card-title mb-0">
                {{ $gettext('Services') }}
            </h2>
        </div>

        <loading :loading="isLoading">
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
        </loading>
    </section>
</template>

<script setup lang="ts">
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {getApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useNotify} from "~/functions/useNotify";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import {useIntervalFn} from "@vueuse/core";
import {computed} from "vue";
import Loading from "~/components/Common/Loading.vue";

const servicesUrl = getApiUrl('/admin/services');

const {axios, axiosSilent} = useAxios();

const {state: services, isLoading, execute: reloadServices} = useRefreshableAsyncState(
    () => axiosSilent.get(servicesUrl.value).then(r => r.data),
    [],
    {
        shallow: true
    }
);

useIntervalFn(
    () => {
        reloadServices()
    },
    computed(() => (!document.hidden) ? 5000 : 15000)
);

const {notifySuccess} = useNotify();

const doRestart = (serviceUrl) => {
    axios.post(serviceUrl).then((resp) => {
        notifySuccess(resp.data.message);
    });
};
</script>
