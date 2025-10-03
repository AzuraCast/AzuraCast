<template>
    <loading :loading="propsLoading" lazy>
        <media v-if="props" v-bind="props"/>
    </loading>
</template>

<script setup lang="ts">
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import Loading from "~/components/Common/Loading.vue";
import Media from "~/components/Stations/Media.vue";
import {StationsVueFilesPropsRequired} from "~/entities/StationMedia.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const propsUrl = getStationApiUrl('/vue/files');

const {axios} = useAxios();

type FilesProps = StationsVueFilesPropsRequired

const {data: props, isLoading: propsLoading} = useQuery<FilesProps>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationMedia,
            'props'
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<FilesProps>(propsUrl.value, {signal});
        return data;
    }
});
</script>
