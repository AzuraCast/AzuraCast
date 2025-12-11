<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Backups') }}
    </h2>

    <loading :loading="propsLoading || settingsLoading" lazy>
        <backups v-if="props && settings" v-bind="props" :settings="settings" @relist="() => relist()"/>
    </loading>
</template>

<script setup lang="ts">
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {ApiAdminVueBackupProps, Settings} from "~/entities/ApiInterfaces.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import Backups from "~/components/Admin/Backups.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const propsUrl = getApiUrl('/admin/vue/backups');
const settingsUrl = getApiUrl('/admin/settings/backup');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueBackupProps>({
    queryKey: [QueryKeys.AdminBackups, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueBackupProps>(propsUrl.value, {signal});
        return data;
    }
});

export type BackupSettings = Required<Pick<Settings,
    | 'backup_enabled'
    | 'backup_time_code'
    | 'backup_exclude_media'
    | 'backup_keep_copies'
    | 'backup_storage_location'
    | 'backup_format'
    | 'backup_last_run'
    | 'backup_last_output'
>>

const {
    data: settings,
    isLoading: settingsLoading,
    refetch: reloadSettings
} = useQuery<BackupSettings>({
    queryKey: [QueryKeys.AdminBackups, 'settings'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<BackupSettings>(settingsUrl.value, {signal});
        return data;
    }
});

const relist = async () => {
    await reloadSettings();
};
</script>
