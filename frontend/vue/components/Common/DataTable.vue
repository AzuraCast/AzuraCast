<template>
    <div
        :id="id"
        style="display: contents"
    >
        <div
            v-if="showToolbar"
            class="datatable-toolbar-top card-body"
        >
            <b-row class="align-items-center mb-2">
                <b-col
                    v-if="showPagination"
                    xl="6"
                    lg="5"
                    md="12"
                    sm="12"
                >
                    <b-pagination
                        v-if="showPagination"
                        v-model="currentPage"
                        :total-rows="totalRows"
                        :per-page="perPage"
                        class="mb-0"
                    />
                </b-col>
                <b-col
                    v-else
                    xl="6"
                    lg="5"
                    md="12"
                    sm="12"
                >
                    &nbsp;
                </b-col>
                <b-col
                    xl="6"
                    lg="7"
                    md="12"
                    sm="12"
                    class="d-flex my-2"
                >
                    <div class="flex-fill">
                        <div class="input-group">
                            <div class="input-group-prepend text-muted">
                                <icon icon="search" />
                            </div>
                            <b-form-input
                                v-model="filter"
                                debounce="200"
                                type="search"
                                class="search-field form-control"
                                :placeholder="$gettext('Search')"
                            />
                        </div>
                    </div>
                    <div class="flex-shrink-1 pl-3 pr-3">
                        <b-btn-group class="actions">
                            <b-button
                                v-b-tooltip.hover
                                variant="default"
                                :title="$gettext('Refresh rows')"
                                @click="onClickRefresh"
                            >
                                <icon icon="refresh" />
                            </b-button>
                            <b-dropdown
                                v-if="paginated"
                                v-b-tooltip.hover
                                variant="default"
                                :text="perPageLabel"
                                :title="$gettext('Rows per page')"
                            >
                                <b-dropdown-item
                                    v-for="pageOption in pageOptions"
                                    :key="pageOption"
                                    :active="(pageOption === perPage)"
                                    @click="setPerPage(pageOption)"
                                >
                                    {{ getPerPageLabel(pageOption) }}
                                </b-dropdown-item>
                            </b-dropdown>
                            <b-dropdown
                                v-if="selectFields"
                                v-b-tooltip.hover
                                variant="default"
                                :title="$gettext('Select displayed fields')"
                            >
                                <template #button-content>
                                    <icon icon="filter_list" />
                                    <span class="caret" />
                                </template>
                                <b-dropdown-form class="pt-3">
                                    <div
                                        v-for="field in selectableFields"
                                        :key="field.key"
                                        class="form-group"
                                    >
                                        <div class="custom-control custom-checkbox">
                                            <input
                                                :id="'chk_field_' + field.key"
                                                v-model="field.visible"
                                                type="checkbox"
                                                class="custom-control-input"
                                                name="is_field_visible"
                                                @change="storeSettings"
                                            >
                                            <label
                                                class="custom-control-label"
                                                :for="'chk_field_'+field.key"
                                            >
                                                {{ field.label }}
                                            </label>
                                        </div>
                                    </div>
                                </b-dropdown-form>
                            </b-dropdown>
                        </b-btn-group>
                    </div>
                </b-col>
            </b-row>
        </div>
        <div class="datatable-main">
            <b-table
                ref="table"
                v-model:current-page="currentPage"
                v-model:sort-by="sortBy"
                v-model:sort-desc="sortDesc"
                show-empty
                striped
                hover
                :selectable="selectable"
                :api-url="apiUrl"
                :per-page="perPage"
                :items="itemProvider"
                :fields="visibleFields"
                :empty-text="$gettext('No records to display.')"
                :empty-filtered-text="$gettext('No records to display.')"
                :responsive="responsive"
                :no-provider-paging="handleClientSide"
                :no-provider-sorting="handleClientSide"
                :no-provider-filtering="handleClientSide"
                tbody-tr-class="align-middle"
                thead-tr-class="align-middle"
                selected-variant=""
                :filter="filter"
                @row-selected="onRowSelected"
                @filtered="onFiltered"
                @refreshed="onRefreshed"
                @sort-changed="onSortChanged"
            >
                <template #head(selected)>
                    <b-form-checkbox
                        :aria-label="$gettext('Select all visible rows')"
                        :checked="allSelected"
                        @change="toggleSelected"
                    />
                </template>
                <template #cell(selected)="{ rowSelected }">
                    <div class="text-muted">
                        <template v-if="rowSelected">
                            <span class="sr-only">{{ $gettext('Deselect') }}</span>
                            <icon icon="check_box" />
                        </template>
                        <template v-else>
                            <span class="sr-only">{{ $gettext('Select') }}</span>
                            <icon icon="check_box_outline_blank" />
                        </template>
                    </div>
                </template>
                <template #table-busy>
                    <div
                        role="alert"
                        aria-live="polite"
                    >
                        <div class="text-center my-2">
                            <div class="progress-circular progress-circular-primary mx-auto mb-3">
                                <div class="progress-circular-wrapper">
                                    <div class="progress-circular-inner">
                                        <div class="progress-circular-left">
                                            <div class="progress-circular-spinner" />
                                        </div>
                                        <div class="progress-circular-gap" />
                                        <div class="progress-circular-right">
                                            <div class="progress-circular-spinner" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{ $gettext('Loading...') }}
                        </div>
                    </div>
                </template>

                <template
                    v-for="(_, slot) of $slots"
                    #[slot]="scope"
                >
                    <slot
                        :name="slot"
                        v-bind="scope"
                    />
                </template>
            </b-table>
        </div>
        <div class="datatable-toolbar-bottom card-body">
            <b-pagination
                v-if="showPagination"
                v-model="currentPage"
                :total-rows="totalRows"
                :per-page="perPage"
                class="mb-0 mt-2"
            />
        </div>
    </div>
</template>

<script>
import store from 'store';
import {forEach, filter, map, defaultTo, includes} from 'lodash';
import Icon from './Icon.vue';
import {defineComponent} from "vue";

/* TODO Options API */

export default defineComponent({
    name: 'DataTable',
    components: {Icon},
    props: {
        id: {
            type: String,
            default: null
        },
        apiUrl: {
            type: String,
            default: null
        },
        items: {
            type: Array,
            default: null
        },
        responsive: {
            type: [String, Boolean],
            default: true
        },
        paginated: {
            type: Boolean,
            default: false
        },
        showToolbar: {
            type: Boolean,
            default: true
        },
        pageOptions: {
            type: Array,
            default: () => [10, 25, 50, 100, 250, 500, 0]
        },
        defaultPerPage: {
            type: Number,
            default: 10
        },
        fields: {
            type: Array,
            required: true
        },
        selectable: {
            type: Boolean,
            default: false
        },
        selectFields: {
            type: Boolean,
            default: false
        },
        handleClientSide: {
            type: Boolean,
            default: false
        },
        requestConfig: {
            type: Function,
            default: null
        },
        requestProcess: {
            type: Function,
            default: null
        }
    },
    emits: [
        'refreshed',
        'row-selected',
        'filtered'
    ],
    data() {
        let allFields = [];
        forEach(this.fields, function (field) {
            allFields.push({
                ...{
                    label: '',
                    isRowHeader: false,
                    sortable: false,
                    selectable: false,
                    visible: true,
                    formatter: null
                },
                ...field
            });
        });

        return {
            allFields: allFields,
            selected: [],
            sortBy: null,
            sortDesc: false,
            storeKey: 'datatable_' + this.id + '_settings',
            filter: null,
            perPage: (this.paginated) ? this.defaultPerPage : 0,
            currentPage: 1,
            totalRows: 0,
            flushCache: false
        };
    },
    computed: {
        visibleFields() {
            let fields = this.allFields.slice();

            if (this.selectable) {
                fields.unshift({
                    key: 'selected',
                    label: '',
                    isRowHeader: false,
                    sortable: false,
                    selectable: false,
                    visible: true
                });
            }

            if (!this.selectFields) {
                return fields;
            }

            return filter(fields, (field) => {
                if (!field.selectable) {
                    return true;
                }

                return field.visible;
            });
        },
        selectableFields() {
            return filter(this.allFields.slice(), (field) => {
                return field.selectable;
            });
        },
        showPagination() {
            return this.paginated && this.perPage !== 0;
        },
        perPageLabel() {
            return this.getPerPageLabel(this.perPage);
        },
        allSelected() {
            return ((this.selected.length === this.totalRows)
                || (this.showPagination && this.selected.length === this.perPage));
        },
        itemProvider() {
            if (this.items !== null) {
                return this.items;
            }

            return (ctx, callback) => {
                return this.loadItems(ctx, callback);
            }
        }
    },
    watch: {
        items(newVal) {
            if (newVal !== null) {
                this.totalRows = newVal.length;
            }
        },
        filter() {
            this.currentPage = 1;
        }
    },
    created() {
        this.loadStoredSettings();
    },
    methods: {
        loadStoredSettings() {
            if (store.enabled && store.get(this.storeKey) !== undefined) {
                let settings = store.get(this.storeKey);

                this.perPage = defaultTo(settings.perPage, this.defaultPerPage);

                forEach(this.selectableFields, (field) => {
                    field.visible = includes(settings.visibleFields, field.key);
                });

                if (settings.sortBy) {
                    this.sortBy = settings.sortBy;
                    this.sortDesc = settings.sortDesc;
                }
            }
        },
        storeSettings() {
            if (!store.enabled) {
                return;
            }

            let settings = {
                'perPage': this.perPage,
                'sortBy': this.sortBy,
                'sortDesc': this.sortDesc,
                'visibleFields': map(this.visibleFields, 'key')
            };

            store.set(this.storeKey, settings);
        },
        getPerPageLabel(num) {
            return (num === 0) ? 'All' : num.toString();
        },
        setPerPage(num) {
            this.perPage = num;
            this.storeSettings();
        },
        onClickRefresh(e) {
            if (e.shiftKey) {
                this.relist();
            } else {
                this.refresh();
            }
        },
        onSortChanged() {
            this.$nextTick(() => {
                this.storeSettings();
            });
        },
        onRefreshed() {
            this.$emit('refreshed');
        },
        refresh() {
            this.$refs.table.refresh();
        },
        navigate() {
            this.filter = null;
            this.currentPage = 1;
            this.flushCache = true;
            this.refresh();
        },
        relist() {
            this.flushCache = true;
            this.refresh();
        },
        setFilter(newTerm) {
            this.filter = newTerm;
        },
        loadItems(ctx) {
            let queryParams = {
                internal: true
            };

            if (this.handleClientSide) {
                queryParams.rowCount = 0;
            } else {
                if (this.paginated) {
                    queryParams.rowCount = ctx.perPage;
                    queryParams.current = (ctx.perPage !== 0) ? ctx.currentPage : 1;
                } else {
                    queryParams.rowCount = 0;
                }

                if (this.flushCache) {
                    queryParams.flushCache = true;
                }

                if (typeof ctx.filter === 'string') {
                    queryParams.searchPhrase = ctx.filter;
                }

                if ('' !== ctx.sortBy) {
                    queryParams.sort = ctx.sortBy;
                    queryParams.sortOrder = (ctx.sortDesc) ? 'DESC' : 'ASC';
                }
            }

            let requestConfig = {params: queryParams};
            if (typeof this.requestConfig === 'function') {
                requestConfig = this.requestConfig(requestConfig);
            }

            return this.axios.get(ctx.apiUrl, requestConfig).then((resp) => {
                this.totalRows = resp.data.total;

                let rows = resp.data.rows;
                if (typeof this.requestProcess === 'function') {
                    rows = this.requestProcess(rows);
                }

                return rows;
            }).catch((err) => {
                this.totalRows = 0;

                console.error(err.response.data.message);
                return [];
            }).finally(() => {
                this.flushCache = false;
            });
        },
        onRowSelected(items) {
            this.selected = items;
            this.$emit('row-selected', items);
        },
        toggleSelected() {
            if (this.allSelected) {
                this.$refs.table.clearSelected();
            } else {
                this.$refs.table.selectAllRows();
            }
        },
        onFiltered(filter) {
            this.$emit('filtered', filter);
        }
    }
});
</script>

<style lang="scss">
div.datatable-main {
    flex: 1;
}

div.datatable-toolbar-top,
div.datatable-toolbar-bottom {
    flex: 0;
    padding: 0;
}

table.b-table {
    td.shrink {
        width: 0.1%;
        white-space: nowrap;
    }
}

table.b-table-selectable {
    thead tr th:nth-child(1),
    tbody tr td:nth-child(1),
    tbody tr th:nth-child(1) {
        padding-right: 0.75rem;
        width: 3rem;
    }

    thead tr th:nth-child(2),
    tbody tr td:nth-child(2),
    tbody tr th:nth-child(2) {
        padding-left: 0.5rem;
    }
}
</style>
