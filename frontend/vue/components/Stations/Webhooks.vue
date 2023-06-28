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
            <b-button
                variant="primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Web Hook') }}
            </b-button>
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

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    nowPlayingUrl: {
        type: String,
        required: true
    }
});

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
        ? 'warning'
        : 'success';
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
