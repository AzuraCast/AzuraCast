<template>
    <div class="row">
        <div class="col-md-8">
            <b-card no-body>
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('SFTP Users') }}
                    </h2>
                </b-card-header>

                <b-card-body body-class="card-padding-sm">
                    <b-button
                        variant="outline-primary"
                        @click.prevent="doCreate"
                    >
                        <icon icon="add" />
                        {{ $gettext('Add SFTP User') }}
                    </b-button>
                </b-card-body>

                <data-table
                    id="station_remotes"
                    ref="datatable"
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
            </b-card>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Connection Information') }}
                    </h2>
                </div>
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
            </div>
        </div>

        <sftp-users-edit-modal
            ref="editModal"
            :create-url="listUrl"
            @relist="relist"
        />
    </div>
</template>

<script>
import DataTable from "~/components/Common/DataTable";
import SftpUsersEditModal from "./SftpUsers/EditModal";
import Icon from "~/components/Common/Icon";

/* TODO Options API */

export default {
    name: 'SftpUsers',
    components: {Icon, SftpUsersEditModal, DataTable},
    props: {
        listUrl: {
            type: String,
            required: true
        },
        connectionInfo: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            fields: [
                {key: 'username', isRowHeader: true, label: this.$gettext('Username'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        relist() {
            this.$refs.datatable.refresh();
        },
        doCreate() {
            this.$refs.editModal.create();
        },
        doEdit(url) {
            this.$refs.editModal.edit(url);
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete SFTP User?')
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        }
    }
}
</script>
