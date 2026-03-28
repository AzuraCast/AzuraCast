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
import {useAxios} from "~/vendor/axios.ts";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import Loading from "~/components/Common/Loading.vue";
import {ApiAdminServiceData} from "~/entities/ApiInterfaces.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const servicesUrl = getApiUrl('/admin/services');

const {axios, axiosSilent} = useAxios();

type ServiceDataRow = Required<ApiAdminServiceData>;

const {data: services, isLoading} = useQuery<ServiceDataRow[]>({
    queryKey: [QueryKeys.AdminIndex, 'services'],
    queryFn: async ({signal}) => {
        const {data} = await axiosSilent.get(servicesUrl.value, {signal});
        return data;
    },
    placeholderData: () => ([]),
    refetchInterval: 5 * 1000
});

const {notifySuccess} = useNotify();

const doRestart = async (serviceUrl: string) => {
    const {data} = await axios.post(serviceUrl);
    notifySuccess(data.message);
};
</script>
