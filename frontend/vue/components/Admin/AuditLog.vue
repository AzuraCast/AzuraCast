<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    {{ $gettext('Audit Log') }}
                </h2>
                <div class="flex-shrink">
                    <date-range-dropdown
                        v-model="dateRange"
                        @update="relist"
                    />
                </div>
            </div>
        </div>
        <data-table
            ref="$dataTable"
            responsive
            paginated
            :fields="fields"
            :api-url="apiUrl"
        >
            <template #cell(operation)="row">
                <span
                    v-if="row.item.operation_text === 'insert'"
                    class="text-success"
                    :title="$gettext('Insert')"
                >
                    <icon
                        class="lg inline"
                        icon="add_circle"
                    />
                </span>
                <span
                    v-else-if="row.item.operation_text === 'delete'"
                    class="text-danger"
                    :title="$gettext('Delete')"
                >
                    <icon
                        class="lg inline"
                        icon="remove_circle"
                    />
                </span>
                <span
                    v-else
                    class="text-primary"
                    :title="$gettext('Update')"
                >
                    <icon
                        class="lg inline"
                        icon="swap_horizontal_circle"
                    />
                </span>
            </template>
            <template #cell(identifier)="row">
                <small>{{ row.item.class }}</small><br>
                {{ row.item.identifier }}
            </template>
            <template #cell(target)="row">
                <template v-if="row.item.target">
                    <small>{{ row.item.target_class }}</small><br>
                    {{ row.item.target }}
                </template>
                <template v-else>
                    {{ $gettext('N/A') }}
                </template>
            </template>
            <template #cell(actions)="row">
                <template v-if="row.item.changes.length > 0">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click="row.toggleDetails"
                    >
                        {{ $gettext('Changes') }}
                    </b-button>
                </template>
            </template>
            <template #row-details="row">
                <table class="table table-bordered">
                    <colgroup>
                        <col width="30%">
                        <col width="35%">
                        <col width="35%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>{{ $gettext('Field Name') }}</th>
                            <th>{{ $gettext('Previous') }}</th>
                            <th>{{ $gettext('Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="change in row.item.changes"
                            :key="change.field"
                        >
                            <td>{{ change.field }}</td>
                            <td>
                                <pre class="changes">{{ change.from }}</pre>
                            </td>
                            <td>
                                <pre class="changes">{{ change.to }}</pre>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>
        </data-table>
    </div>
</template>

<script setup>
import {DateTime} from "luxon";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAzuraCast} from "~/vendor/azuracast";
import DataTable from "~/components/Common/DataTable.vue";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown.vue";
import Icon from "~/components/Common/Icon.vue";

const props = defineProps({
    baseApiUrl: {
        type: String,
        required: true,
    }
});

const dateRange = ref({
    startDate: DateTime.now().minus({days: 13}).toJSDate(),
    endDate: DateTime.now().toJSDate(),
});

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();

const fields = [
    {
        key: 'timestamp',
        label: $gettext('Date/Time'),
        sortable: false,
        formatter: (value) => {
            return DateTime.fromSeconds(value).toLocaleString(
                {
                    ...DateTime.DATETIME_SHORT, ...timeConfig
                }
            );
        }
    },
    {key: 'user', label: $gettext('User'), sortable: false},
    {key: 'operation', isRowHeader: true, label: $gettext('Operation'), sortable: false},
    {key: 'identifier', label: $gettext('Identifier'), sortable: false},
    {key: 'target', label: $gettext('Target'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false}
];

const apiUrl = computed(() => {
    let apiUrl = new URL(props.baseApiUrl, document.location);

    let apiUrlParams = apiUrl.searchParams;
    apiUrlParams.set('start', DateTime.fromJSDate(dateRange.value.startDate).toISO());
    apiUrlParams.set('end', DateTime.fromJSDate(dateRange.value.endDate).toISO());

    return apiUrl.toString();
});

const $dataTable = ref(); // DataTable Template Ref

const relist = () => {
    $dataTable.value.relist();
};
</script>

<style lang="scss">
pre.changes {
    max-width: 250px;
    margin-bottom: 0;
}
</style>
