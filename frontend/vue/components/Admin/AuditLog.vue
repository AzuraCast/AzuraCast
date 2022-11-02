<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    <translate key="lang_title">Audit Log</translate>
                </h2>
                <div class="flex-shrink">
                    <date-range-dropdown v-model="dateRange" @update="relist"></date-range-dropdown>
                </div>
            </div>
        </div>
        <data-table ref="datatable" responsive paginated
                    :fields="fields" :apiUrl="apiUrl">
            <template #cell(date_time)="row">
                {{ formatTimestamp(row.item.timestamp) }}
            </template>
            <template #cell(operation)="row">
                <span class="text-success" v-if="row.item.operation_text === 'insert'" :title="langInsert">
                    <icon class="lg inline" icon="add_circle"></icon>
                </span>
                <span class="text-danger" v-else-if="row.item.operation_text === 'delete'" :title="langDelete">
                    <icon class="lg inline" icon="remove_circle"></icon>
                </span>
                <span class="text-primary" v-else>
                    <icon class="lg inline" icon="swap_horizontal_circle"></icon>
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
                    <translate key="lang_target_not_available">N/A</translate>
                </template>
            </template>
            <template #cell(actions)="row">
                <template v-if="row.item.changes.length > 0">
                    <b-button size="sm" variant="primary" @click="row.toggleDetails">
                        <translate key="lang_changes_btn">Changes</translate>
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
                        <th>
                            <translate key="lang_col_field">Field Name</translate>
                        </th>
                        <th>
                            <translate key="lang_col_previous">Previous</translate>
                        </th>
                        <th>
                            <translate key="lang_col_updated">Updated</translate>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="change in row.item.changes" :key="change.field">
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

<style lang="scss">
pre.changes {
    max-width: 250px;
    margin-bottom: 0;
}
</style>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import {DateTime} from 'luxon';

export default {
    name: 'AdminAuditLog',
    components: {DateRangeDropdown, Icon, DataTable},
    props: {
        baseApiUrl: String,
    },
    data() {
        return {
            dateRange: {
                startDate: DateTime.now().minus({days: 13}).toJSDate(),
                endDate: DateTime.now().toJSDate(),
            },
            fields: [
                {key: 'date_time', label: this.$gettext('Date/Time'), sortable: false},
                {key: 'user', label: this.$gettext('User'), sortable: false},
                {key: 'operation', isRowHeader: true, label: this.$gettext('Operation'), sortable: false},
                {key: 'identifier', label: this.$gettext('Identifier'), sortable: false},
                {key: 'target', label: this.$gettext('Target'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false}
            ],
        };
    },
    computed: {
        langInsert() {
            return this.$gettext('Insert');
        },
        langUpdate() {
            return this.$gettext('Update');
        },
        langDelete() {
            return this.$gettext('Delete');
        },
        apiUrl() {
            let apiUrl = new URL(this.baseApiUrl, document.location);

            let apiUrlParams = apiUrl.searchParams;
            apiUrlParams.set('start', DateTime.fromJSDate(this.dateRange.startDate).toISO());
            apiUrlParams.set('end', DateTime.fromJSDate(this.dateRange.endDate).toISO());

            return apiUrl.toString();
        },
    },
    methods: {
        relist() {
            this.$refs.datatable.relist();
        },
        formatTimestamp(unix_timestamp) {
            return DateTime.fromSeconds(unix_timestamp).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...App.time_config}
            );
        }
    }
}
</script>
