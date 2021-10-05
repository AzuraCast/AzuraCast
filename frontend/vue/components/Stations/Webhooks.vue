<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Web Hooks</h2>
            </b-card-header>

            <info-card>
                <translate key="lang_info_card">Web hooks let you connect to external web services and broadcast changes to your station to them.</translate>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_webhook">Add Web Hook</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_webhooks" :show-toolbar="false" :fields="fields"
                        :api-url="listUrl">
                <template #cell(name)="row">
                    <big>{{ row.item.name }}</big><br>
                    {{ getWebhookName(row.item.type) }}
                    <span v-if="!row.item.is_enabled" class="label label-danger">
                        <translate key="lang_webhook_disabled">Disabled</translate>
                    </span>
                </template>
                <template #cell(triggers)="row">
                    {{ getTriggerNames(row.item.triggers) }}
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="warning" @click.prevent="doToggle(row.item.links.toggle)">
                            {{ langToggleButton(row.item) }}
                        </b-button>
                        <b-button size="sm" variant="default" @click.prevent="doTest(row.item.links.test)">
                            <translate key="lang_btn_test">Test</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <streaming-log-modal ref="logModal"></streaming-log-modal>
        <edit-modal ref="editModal" :create-url="listUrl" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Webhooks/EditModal';
import Icon from '~/components/Common/Icon';
import confirmDelete from "~/functions/confirmDelete";
import InfoCard from "~/components/Common/InfoCard";
import _ from 'lodash';
import StreamingLogModal from "~/components/Common/StreamingLogModal";

export default {
    name: 'StationWebhooks',
    components: {StreamingLogModal, InfoCard, Icon, EditModal, DataTable},
    props: {
        listUrl: String,
        webhookTypes: Object,
        webhookTriggers: Object
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Name/Type'), sortable: false},
                {key: 'triggers', label: this.$gettext('Triggers'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        langToggleButton(record) {
            return (record.is_enabled)
                ? this.$gettext('Disable')
                : this.$gettext('Enable');
        },
        getWebhookName(key) {
            return _.get(this.webhookTypes, [key, 'name'], '');
        },
        getTriggerNames(triggers) {
            return _.map(triggers, (trigger) => {
                return _.get(this.webhookTriggers, trigger, '');
            }).join(', ');
        },
        relist() {
            this.$refs.datatable.refresh();
        },
        doCreate() {
            this.$refs.editModal.create();
        },
        doEdit(url) {
            this.$refs.editModal.edit(url);
        },
        doToggle(url) {
            this.$wrapWithLoading(
                this.axios.put(url)
            ).then((resp) => {
                this.$notifySuccess(resp.data.message);
                this.relist();
            });
        },
        doTest(url) {
            this.$wrapWithLoading(
                this.axios.put(url)
            ).then((resp) => {
                resp.data.this.$notifySuccess(resp.data.links.log);
                this.relist();
            });
        },
        doDelete(url) {
            confirmDelete({
                title: this.$gettext('Delete Web Hook?'),
                confirmButtonText: this.$gettext('Delete'),
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
};
</script>
