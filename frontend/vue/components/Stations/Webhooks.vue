<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Web Hooks') }}
            </h2>
        </b-card-header>

        <info-card>
            {{
                $gettext('Web hooks let you connect to external web services and broadcast changes to your station to them.')
            }}
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Web Hook') }}
            </b-button>
        </b-card-body>

        <data-table
            id="station_webhooks"
            ref="datatable"
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                <div class="typography-subheading">
                    {{ row.item.name }}
                </div>
                {{ getWebhookName(row.item.type) }}
                <b-badge
                    v-if="!row.item.is_enabled"
                    variant="danger"
                >
                    {{ $gettext('Disabled') }}
                </b-badge>
            </template>
            <template #cell(triggers)="row">
                <div
                    v-for="(name, index) in getTriggerNames(row.item.triggers)"
                    :key="row.item.id+'_'+index"
                    class="small"
                >
                    {{ name }}
                </div>
            </template>
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
                        :variant="getToggleVariant(row.item)"
                        @click.prevent="doToggle(row.item.links.toggle)"
                    >
                        {{ langToggleButton(row.item) }}
                    </b-button>
                    <b-button
                        size="sm"
                        variant="default"
                        @click.prevent="doTest(row.item.links.test)"
                    >
                        {{ $gettext('Test') }}
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

    <streaming-log-modal ref="logModal" />
    <edit-modal
        ref="editModal"
        :create-url="listUrl"
        :webhook-types="webhookTypes"
        :trigger-titles="langTriggerTitles"
        :trigger-descriptions="langTriggerDescriptions"
        :now-playing-url="nowPlayingUrl"
        @relist="relist"
    />
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Webhooks/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from "~/components/Common/InfoCard";
import {get, map} from 'lodash';
import StreamingLogModal from "~/components/Common/StreamingLogModal";

/* TODO Options API */

export default {
    name: 'StationWebhooks',
    components: {StreamingLogModal, InfoCard, Icon, EditModal, DataTable},
    props: {
        listUrl: {
            type: String,
            required: true
        },
        nowPlayingUrl: {
            type: String,
            required: true
        },
        webhookTypes: {
            type: Object,
            required: true
        }
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
    computed: {
        langTriggerTitles() {
            return {
                song_changed: this.$gettext('Song Change'),
                song_changed_live: this.$gettext('Song Change (Live Only)'),
                listener_gained: this.$gettext('Listener Gained'),
                listener_lost: this.$gettext('Listener Lost'),
                live_connect: this.$gettext('Live Streamer/DJ Connected'),
                live_disconnect: this.$gettext('Live Streamer/DJ Disconnected'),
                station_offline: this.$gettext('Station Goes Offline'),
                station_online: this.$gettext('Station Goes Online'),
            }
        },
        langTriggerDescriptions() {
            return {
                song_changed: this.$gettext('Any time the currently playing song changes'),
                song_changed_live: this.$gettext('When the song changes and a live streamer/DJ is connected'),
                listener_gained: this.$gettext('Any time the listener count increases'),
                listener_lost: this.$gettext('Any time the listener count decreases'),
                live_connect: this.$gettext('Any time a live streamer/DJ connects to the stream'),
                live_disconnect: this.$gettext('Any time a live streamer/DJ disconnects from the stream'),
                station_offline: this.$gettext('When the station broadcast goes offline'),
                station_online: this.$gettext('When the station broadcast comes online'),
            }
        }
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
            return get(this.webhookTypes, [key, 'name'], '');
        },
        getTriggerNames(triggers) {
            return map(triggers, (trigger) => {
                return get(this.langTriggerTitles, trigger, '');
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
