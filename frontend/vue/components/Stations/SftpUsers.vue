<template>
    <div class="row">
        <div class="col-md-8">
            <card-page
                header-id="hdr_sftp_users"
                :title="$gettext('SFTP Users')"
            >
                <template #actions>
                    <b-button
                        variant="outline-primary"
                        @click.prevent="doCreate"
                    >
                        <icon icon="add" />
                        {{ $gettext('Add SFTP User') }}
                    </b-button>
                </template>

                <data-table
                    id="station_remotes"
                    ref="$datatable"
                    :show-toolbar="false"
                    :fields="fields"
                    :api-url="listUrl"
                >
                    <template #cell(actions)="row">
                        <b-button-group size="sm">
                            <b-button
                                size="sm"
                                variant="primary"
                                @click.prevent="doEdit(row.item.links.self)"
                            >
                                {{ $gettext('Edit') }}
                            </b-button>
                            <b-button
                                size="sm"
                                variant="danger"
                                @click.prevent="doDelete(row.item.links.self)"
                            >
                                {{ $gettext('Delete') }}
                            </b-button>
                        </b-button-group>
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

<script setup>
import DataTable from "~/components/Common/DataTable";
import SftpUsersEditModal from "./SftpUsers/EditModal";
import Icon from "~/components/Common/Icon";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    connectionInfo: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'username', isRowHeader: true, label: $gettext('Username'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete SFTP User?'),
    relist
);
</script>
