<template>
    <loading :loading="propsLoading" lazy>
        <permissions v-if="props" v-bind="props"/>
    </loading>
</template>

<script setup lang="ts">
import {ApiAdminVuePermissionsProps} from "~/entities/ApiInterfaces.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import Permissions from "~/components/Admin/Permissions.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const propsUrl = getApiUrl('/admin/vue/permissions');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVuePermissionsProps>({
    queryKey: [QueryKeys.AdminPermissions, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVuePermissionsProps>(propsUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        stations: {},
        globalPermissions: {},
        stationPermissions: {}
    })
});
</script>
