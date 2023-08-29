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
                    id="station_remotes"
                    ref="$datatable"
                    :show-toolbar="false"
                    :fields="fields"
                    :api-url="listUrl"
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
                    <dl>
                        <dt class="mb-1">
                            {{ $gettext('Server:') }}
                        </dt>
                        <dd><code>{{ connectionInfo.url }}</code></dd>

                        <dd v-if="connectionInfo.ip">
                            {{ $gettext('You may need to connect directly to your IP address:') }}
                            <code>{{ connectionInfo.ip }}</code>
                        </dd>

                        <dt class="mb-1">
                            {{ $gettext('Port:') }}
                        </dt>
                        <dd><code>{{ connectionInfo.port }}</code></dd>
                    </dl>
                </div>
            </card-page>
        </div>

        <sftp-users-edit-modal
            ref="$editModal"
            :create-url="listUrl"
            @relist="relist"
        />
    </div>
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from "~/components/Common/DataTable.vue";
import SftpUsersEditModal from "./SftpUsers/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getStationApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const props = defineProps({
    connectionInfo: {
        type: Object,
        required: true
    }
});

const listUrl = getStationApiUrl('/sftp-users');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'username', isRowHeader: true, label: $gettext('Username'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete SFTP User?'),
    relist
);
</script>
