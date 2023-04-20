<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_web_hooks"
    >
        <b-card-header header-bg-variant="primary-dark">
            <h2
                id="hdr_web_hooks"
                class="card-title"
            >
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
            ref="$datatable"
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
    </section>

    <streaming-log-modal ref="$logModal" />
    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :webhook-types="webhookTypes"
        :trigger-titles="langTriggerTitles"
        :trigger-descriptions="langTriggerDescriptions"
        :now-playing-url="nowPlayingUrl"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Webhooks/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from "~/components/Common/InfoCard";
import {get, map} from 'lodash';
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";

const props = defineProps({
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
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name/Type'), sortable: true},
    {key: 'triggers', label: $gettext('Triggers'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const langTriggerTitles = {
    song_changed: $gettext('Song Change'),
    song_changed_live: $gettext('Song Change (Live Only)'),
    listener_gained: $gettext('Listener Gained'),
    listener_lost: $gettext('Listener Lost'),
    live_connect: $gettext('Live Streamer/DJ Connected'),
    live_disconnect: $gettext('Live Streamer/DJ Disconnected'),
    station_offline: $gettext('Station Goes Offline'),
    station_online: $gettext('Station Goes Online'),
};

const langTriggerDescriptions = {
    song_changed: $gettext('Any time the currently playing song changes'),
    song_changed_live: $gettext('When the song changes and a live streamer/DJ is connected'),
    listener_gained: $gettext('Any time the listener count increases'),
    listener_lost: $gettext('Any time the listener count decreases'),
    live_connect: $gettext('Any time a live streamer/DJ connects to the stream'),
    live_disconnect: $gettext('Any time a live streamer/DJ disconnects from the stream'),
    station_offline: $gettext('When the station broadcast goes offline'),
    station_online: $gettext('When the station broadcast comes online'),
};

const langToggleButton = (record) => {
    return (record.is_enabled)
        ? $gettext('Disable')
        : $gettext('Enable');
};

const getToggleVariant = (record) => {
    return (record.is_enabled)
        ? 'warning'
        : 'success';
};

const getWebhookName = (key) => {
    return get(props.webhookTypes, [key, 'name'], '');
};

const getTriggerNames = (triggers) => {
    return map(triggers, (trigger) => {
        return get(langTriggerTitles, trigger, '');
    });
};

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doToggle = (url) => {
    wrapWithLoading(
        axios.put(url)
    ).then((resp) => {
        notifySuccess(resp.data.message);
        relist();
    });
};

const $logModal = ref(); // Template Ref

const doTest = (url) => {
    wrapWithLoading(
        axios.put(url)
    ).then((resp) => {
        $logModal.value.show(resp.data.links.log);
    });
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Web Hook?'),
    relist
);
</script>
