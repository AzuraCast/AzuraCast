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
            :fields="fields"
            :provider="listItemProvider"
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
                        <button
                            type="button"
                            class="btn btn-info"
                            @click="doClone(item.links.clone)"
                        >
                            {{ $gettext('Duplicate') }}
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
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Stations/Webhooks/EditModal.vue";
import {get} from "es-toolkit/compat";
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed, useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {useTriggerDetails, useTypeDetails} from "~/entities/Webhooks";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {ApiTaskWithLog, HasLinks, StationWebhook, WebhookTriggers, WebhookTypes} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useStationId} from "~/functions/useStationQuery.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl, getStationApiUrl} = useApiRouter();

const listUrl = getStationApiUrl('/webhooks');

const id = useStationId();
const nowPlayingUrl = getApiUrl(computed(() => `/nowplaying/${id.value}`));

const {$gettext} = useTranslate();

type Row = Required<StationWebhook & HasLinks>;

const fields: DataTableField<Row>[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Name/Type'), sortable: true},
    {key: 'triggers', label: $gettext('Triggers'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider<Row>(
    listUrl,
    queryKeyWithStation([
        QueryKeys.StationWebhooks
    ])
);

const relist = () => {
    void listItemProvider.refresh();
};

const langTypeDetails = useTypeDetails();
const langTriggerDetails = useTriggerDetails();

const langToggleButton = (record: Row) => {
    return (record.is_enabled)
        ? $gettext('Disable')
        : $gettext('Enable');
};

const getToggleVariant = (record: Row) => {
    return (record.is_enabled)
        ? 'btn-warning'
        : 'btn-success';
};

const isWebhookSupported = (key: WebhookTypes) => {
    return (key in langTypeDetails);
}

const getWebhookName = (key: WebhookTypes) => {
    return get(langTypeDetails, [key, 'title'], '');
};

const getTriggerNames = (triggers: WebhookTriggers[]) => {
    return triggers.map((trigger) => {
        return get(langTriggerDetails, [trigger, 'title'], '');
    });
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doToggle = async (url: string) => {
    const {data} = await axios.put(url);

    notifySuccess(data.message);
    relist();
};

const doClone = async (url: string) => {
    await axios.post(url);

    notifySuccess($gettext('Webhook duplicated.'));
    relist();
};

const $logModal = useTemplateRef('$logModal');

const doTest = async (url: string) => {
    const {data} = await axios.put<ApiTaskWithLog>(url);
    $logModal.value?.show(data.logUrl);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Web Hook?'),
    () => relist()
);
</script>
