<template>
    <loading :loading="propsLoading" lazy>
        <media v-bind="props"/>
    </loading>
</template>

<script setup lang="ts">
import {ApiStationsVueFilesProps} from "~/entities/ApiInterfaces.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import Loading from "~/components/Common/Loading.vue";
import {getStationApiUrl} from "~/router.ts";
import Media from "~/components/Stations/Media.vue";

const propsUrl = getStationApiUrl('/vue/files');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiStationsVueFilesProps>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationMedia,
            'props'
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiStationsVueFilesProps>(propsUrl.value, {signal});
        return data;
    }
});
</script>
