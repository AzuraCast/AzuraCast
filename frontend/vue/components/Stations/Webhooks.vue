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

            <data-table ref="datatable" id="station_webhooks" :fields="fields"
                        :api-url="listUrl">
                <template #cell(name)="row">
                    <big>{{ row.item.name }}</big><br>
                    {{ getWebhookName(row.item.type) }}
                    <b-badge v-if="!row.item.is_enabled" variant="danger">
                        <translate key="lang_webhook_disabled">Disabled</translate>
                    </b-badge>
                </template>
                <template #cell(triggers)="row">
                    <div v-for="(name, index) in getTriggerNames(row.item.triggers)" :key="row.item.id+'_'+index"
                         class="small">
                        {{ name }}
                    </div>
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" :variant="getToggleVariant(row.item)"
                                  @click.prevent="doToggle(row.item.links.toggle)">
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
        <edit-modal ref="editModal" :create-url="listUrl" :webhook-types="webhookTypes"
                    :webhook-triggers="webhookTriggers" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Webhooks/EditModal';
import Icon from '~/components/Common/Icon';
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
                {key: 'name', isRowHeader: true, label: this.$gettext('Name/Type'), sortable: true},
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
        getToggleVariant(record) {
            return (record.is_enabled)
                ? 'warning'
                : 'success';
        },
        getWebhookName(key) {
            return _.get(this.webhookTypes, [key, 'name'], '');
        },
        getTriggerNames(triggers) {
            return _.map(triggers, (trigger) => {
                return _.get(this.webhookTriggers, trigger, '');
            });
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
                this.$refs.logModal.show(resp.data.links.log);
            });
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Web Hook?'),
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
