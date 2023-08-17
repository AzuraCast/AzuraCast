<template>
    <card-page :title="$gettext('Web Hooks')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Web hooks let you connect to external web services and broadcast changes to your station to them.')
                }}
            </p>
        </template>
        <template #actions>
            <button
                type="button"
                class="btn btn-primary"
                @click="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Web Hook') }}
                </span>
            </button>
        </template>

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

                <div
                    v-if="!row.item.is_enabled"
                    class="badge bg-danger"
                >
                    {{ $gettext('Disabled') }}
                </div>
            </template>
            <template #cell(triggers)="row">
                <template v-if="row.item.triggers.length > 0">
                    <div
                        v-for="(name, index) in getTriggerNames(row.item.triggers)"
                        :key="row.item.id+'_'+index"
                        class="small"
                    >
                        {{ name }}
                    </div>
                </template>
                <template v-else>
&nbsp;
                </template>
            </template>
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
                        class="btn"
                        :class="getToggleVariant(row.item)"
                        @click="doToggle(row.item.links.toggle)"
                    >
                        {{ langToggleButton(row.item) }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="doTest(row.item.links.test)"
                    >
                        {{ $gettext('Test') }}
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

    <streaming-log-modal ref="$logModal" />
    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :type-details="langTypeDetails"
        :trigger-details="langTriggerDetails"
        :now-playing-url="nowPlayingUrl"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Webhooks/EditModal';
import Icon from '~/components/Common/Icon';
import {get, map} from 'lodash';
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {useTriggerDetails, useTypeDetails} from "~/components/Entity/Webhooks";
import CardPage from "~/components/Common/CardPage.vue";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {getApiUrl, getStationApiUrl} from "~/router";

const listUrl = getStationApiUrl('/webhooks');

const {id} = useAzuraCastStation();
const nowPlayingUrl = getApiUrl(`/nowplaying/${id}`);

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name/Type'), sortable: true},
    {key: 'triggers', label: $gettext('Triggers'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const langTypeDetails = useTypeDetails();
const langTriggerDetails = useTriggerDetails();

const langToggleButton = (record) => {
    return (record.is_enabled)
        ? $gettext('Disable')
        : $gettext('Enable');
};

const getToggleVariant = (record) => {
    return (record.is_enabled)
        ? 'btn-warning'
        : 'btn-success';
};

const getWebhookName = (key) => {
    return get(langTypeDetails, [key, 'title'], '');
};

const getTriggerNames = (triggers) => {
    return map(triggers, (trigger) => {
        return get(langTriggerDetails, [trigger, 'title'], '');
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
