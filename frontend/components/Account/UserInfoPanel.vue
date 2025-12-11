<template>
    <loading :loading="isLoading" lazy>
        <div v-if="user" class="card-header text-bg-primary d-flex flex-wrap align-items-center">
            <avatar
                v-if="user.avatar.url_128"
                class="flex-shrink-0 me-3"
                :url="user.avatar.url_128"
                :service="user.avatar.service_name"
                :service-url="user.avatar.service_url"
            />

            <div class="flex-fill">
                <h2
                    v-if="user.name"
                    class="card-title mt-0"
                >
                    {{ user.name }}
                </h2>
                <h2
                    v-else
                    class="card-title"
                >
                    {{ $gettext('AzuraCast User') }}
                </h2>
                <h3 class="card-subtitle">
                    {{ user.email }}
                </h3>

                <div
                    v-if="user.roles.length > 0"
                    class="mt-2"
                >
                    <span
                        v-for="role in user.roles"
                        :key="role.id"
                        class="badge text-bg-secondary me-2"
                    >{{ role.name }}</span>
                </div>
            </div>

            <div
                v-if="slots.default"
                class="flex-md-shrink-0 mt-3 mt-md-0 buttons"
            >
                <slot />
            </div>
        </div>
    </loading>
</template>
<script setup lang="ts">
import Avatar from "~/components/Common/Avatar.vue";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import Loading from "../Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const slots = defineSlots();

const {axios} = useAxios();

const {getApiUrl} = useApiRouter();
const userUrl = getApiUrl('/frontend/account/me');

type Row = {
    name: string | null,
    email: string,
    avatar: {
        url_32: string,
        url_64: string,
        url_128: string,
        service_name: string,
        service_url: string
    },
    roles: {
        id: number,
        name: string
    }[]
}

const {data: user, isLoading, refetch} = useQuery<Row>({
    queryKey: [QueryKeys.AccountIndex, 'profile'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<Row>(userUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        name: null,
        email: '',
        avatar: {
            url_32: '',
            url_64: '',
            url_128: '',
            service_name: '',
            service_url: ''
        },
        roles: [],
    }),
});

const reload = () => {
    void refetch();
};

defineExpose({
    reload
});
</script>
