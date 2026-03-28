<template>
    <div class="row">
        <div class="col-md-8">
            <card-page
                header-id="hdr_sftp_users"
                :title="$gettext('SFTP Users')"
            >
                <template #actions>
                    <add-button
                        :text="$gettext('Add SFTP User')"
                        @click="doCreate"
                    />
                </template>

                <data-table
                    id="station_sftp_users"
                    :show-toolbar="false"
                    :fields="fields"
                    :provider="listItemProvider"
                >
                    <template #cell(actions)="row">
                        <div class="btn-group btn-group-sm">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="doEdit(row.item.links.self)"
                            >
                                {{ $gettext('Edit') }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger"
                                @click="doDelete(row.item.links.self)"
                            >
                                {{ $gettext('Delete') }}
                            </button>
                        </div>
                    </template>
                </data-table>
            </card-page>
        </div>
        <div class="col-md-4">
            <card-page
                header-id="hdr_connection_info"
                :title="$gettext('Connection Information')"
            >
                <div class="card-body">
                    <loading :loading="propsLoading" lazy>
                        <dl v-if="props">
                            <dt class="mb-1">
                                {{ $gettext('Server:') }}
                            </dt>
                            <dd><code>{{ props.connectionUrl }}</code></dd>

                            <dd v-if="props.connectionIp">
                                {{ $gettext('You may need to connect directly to your IP address:') }}
                                <code>{{ props.connectionIp }}</code>
                            </dd>

                            <dt class="mb-1">
                                {{ $gettext('Port:') }}
                            </dt>
                            <dd><code>{{ props.connectionPort }}</code></dd>
                        </dl>
                    </loading>
                </div>
            </card-page>
        </div>

        <sftp-users-edit-modal
            ref="$editModal"
            :create-url="listUrl"
            @relist="() => relist()"
        />
    </div>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import SftpUsersEditModal from "~/components/Stations/SftpUsers/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import {ApiStationsVueSftpUsersProps} from "~/entities/ApiInterfaces.ts";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const propsUrl = getStationApiUrl('/vue/sftp_users');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiStationsVueSftpUsersProps>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationSftpUsers,
            'props'
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiStationsVueSftpUsersProps>(propsUrl.value, {signal});
        return data;
    }
});

const listUrl = getStationApiUrl('/sftp-users');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'username', isRowHeader: true, label: $gettext('Username'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation(
        [
            QueryKeys.StationSftpUsers,
            'data'
        ]
    )
);

const relist = () => {
    void listItemProvider.refresh();
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete SFTP User?'),
    () => relist()
);
</script>
