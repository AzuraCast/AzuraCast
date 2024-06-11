<template>
    <div class="card-header text-bg-primary d-flex flex-wrap align-items-center">
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
</template>
<script setup lang="ts">
import Avatar from "~/components/Common/Avatar.vue";
import {useAxios} from "~/vendor/axios.ts";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import {getApiUrl} from "~/router.ts";

const slots = defineSlots();

const {axios} = useAxios();

const userUrl = getApiUrl('/frontend/account/me');

const {state: user, execute: reload} = useRefreshableAsyncState(
    () => axios.get(userUrl.value).then((r) => r.data),
    {
        name: null,
        email: null,
        avatar: {
            url_128: null,
            service_name: null,
            service_url: null
        },
        roles: [],
    },
);

defineExpose({
    reload
});
</script>
