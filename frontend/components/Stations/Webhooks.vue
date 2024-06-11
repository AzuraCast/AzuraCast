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
            <add-button
                :text="$gettext('Add Web Hook')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="station_webhooks"
            ref="$datatable"
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(name)="{item}">
                <div class="typography-subheading">
                    {{ item.name }}
                </div>

                <template v-if="isWebhookSupported(item.type)">
                    {{ getWebhookName(item.type) }}
                    <div
                        v-if="!item.is_enabled"
                        class="badge bg-danger"
                    >
                        {{ $gettext('Disabled') }}
                    </div>
                </template>
                <template v-else>
                    {{
                        $gettext('This web hook is no longer supported. Removing it is recommended.')
                    }}
                </template>
            </template>
            <template #cell(triggers)="{item}">
                <template v-if="isWebhookSupported(item.type) && item.triggers.length > 0">
                    <div
                        v-for="(name, index) in getTriggerNames(item.triggers)"
                        :key="item.id+'_'+index"
                        class="small"
                    >
                        {{ name }}
                    </div>
                </template>
                <template v-else>
&nbsp;
                </template>
            </template>
            <template #cell(actions)="{item}">
                <div class="btn-group btn-group-sm">
                    <template v-if="isWebhookSupported(item.type)">
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="doEdit(item.links.self)"
                        >
                            {{ $gettext('Edit') }}
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="getToggleVariant(item)"
                            @click="doToggle(item.links.toggle)"
                        >
                            {{ langToggleButton(item) }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="doTest(item.links.test)"
                        >
                            {{ $gettext('Test') }}
                        </button>
                    </template>
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(item.links.self)"
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

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import EditModal from './Webhooks/EditModal.vue';
import {get, map} from 'lodash';
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {useTriggerDetails, useTypeDetails} from "~/entities/Webhooks";
import CardPage from "~/components/Common/CardPage.vue";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {getApiUrl, getStationApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const listUrl = getStationApiUrl('/webhooks');

const {id} = useAzuraCastStation();
const nowPlayingUrl = getApiUrl(`/nowplaying/${id}`);

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
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

const isWebhookSupported = (key) => {
    return (key in langTypeDetails);
}

const getWebhookName = (key) => {
    return get(langTypeDetails, [key, 'title'], '');
};

const getTriggerNames = (triggers) => {
    return map(triggers, (trigger) => {
        return get(langTriggerDetails, [trigger, 'title'], '');
    });
};

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doToggle = (url) => {
    axios.put(url).then((resp) => {
        notifySuccess(resp.data.message);
        relist();
    });
};

const $logModal = ref<InstanceType<typeof StreamingLogModal> | null>(null);

const doTest = (url) => {
    axios.put(url).then((resp) => {
        $logModal.value?.show(resp.data.links.log);
    });
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Web Hook?'),
    relist
);
</script>
