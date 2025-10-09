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
                {{ $gettext('Edit Station Settings') }}
            </h3>
        </div>
        <div class="card-body">
            <loading :loading="propsLoading" lazy>
                <admin-stations-form
                    v-if="props"
                    v-bind="props"
                    is-edit-mode
                    :edit-url="editUrl"
                    @submitted="onSubmitted"
                />
            </loading>
        </div>
    </section>
</template>

<script setup lang="ts">
import AdminStationsForm from "~/components/Admin/Stations/StationForm.vue";
import {useRouter} from "vue-router";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {ApiAdminVueStationsFormProps} from "~/entities/ApiInterfaces.ts";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const editUrl = getStationApiUrl('/profile/edit');
const propsUrl = getStationApiUrl('/vue/profile/edit');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueStationsFormProps>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationProfile,
        'edit'
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueStationsFormProps>(propsUrl.value, {signal});
        return data;
    }
});

const router = useRouter();

const onSubmitted = async () => {
    await router.push({
        name: 'stations:index'
    });
    
    window.location.reload();
}
</script>
