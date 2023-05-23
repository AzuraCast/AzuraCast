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
                                v-model="searchPhrase"
                                debounce="500"
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
                                    @click="settings.perPage = pageOption"
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
                                    <b-form-checkbox-group
                                        v-model="settings.visibleFieldKeys"
                                        :options="selectableFields"
                                        value-field="key"
                                        text-field="label"
                                        stacked
                                    />
                                </b-dropdown-form>
                            </b-dropdown>
                        </b-btn-group>
                    </div>
                </b-col>
            </b-row>
        </div>
        <div class="datatable-main">
            <b-table
                ref="$table"
                v-model:current-page="currentPage"
                v-model:sort-by="settings.sortBy"
                v-model:sort-desc="settings.sortDesc"
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
                :filter="searchPhrase"
                @row-selected="onRowSelected"
                @filtered="onFiltered"
                @refreshed="onRefreshed"
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

<script setup>
import {filter, map, includes, isEmpty} from 'lodash';
import Icon from './Icon.vue';
import {computed, ref, toRef, watch} from "vue";
import {useLocalStorage} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
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
});

const emit = defineEmits([
    'refreshed',
    'row-selected',
    'filtered'
]);

const selectedRows = ref([]);
const searchPhrase = ref(null);
const currentPage = ref(1);
const totalRows = ref(0);
const flushCache = ref(false);

watch(searchPhrase, () => {
    currentPage.value = 1;
});

watch(toRef(props, 'items'), (newVal) => {
    if (newVal !== null) {
        totalRows.value = newVal.length;
    }
});

const allFields = computed(() => {
    return map(props.fields, (field) => {
        return {
            label: '',
            isRowHeader: false,
            sortable: false,
            selectable: false,
            visible: true,
            formatter: null,
            ...field
        };
    });
});

const selectableFields = computed(() => {
    return filter({...allFields.value}, (field) => {
        return field.selectable;
    });
});

const defaultSelectableFields = computed(() => {
    return filter({...selectableFields.value}, (field) => {
        return field.visible;
    });
});

const settings = useLocalStorage(
    'datatable_' + props.id + '_settings',
    {
        sortBy: null,
        sortDesc: false,
        perPage: props.defaultPerPage,
        visibleFieldKeys: map(defaultSelectableFields.value, (field) => field.key),
    },
    {
        mergeDefaults: true
    }
);

const visibleFieldKeys = computed(() => {
    if (!isEmpty(settings.value.visibleFieldKeys)) {
        return settings.value.visibleFieldKeys;
    }

    return map(defaultSelectableFields.value, (field) => field.key);
});

const perPage = computed(() => {
    return settings.value?.perPage ?? props.defaultPerPage;
});

const visibleFields = computed(() => {
    let fields = allFields.value.slice();

    if (props.selectable) {
        fields.unshift({
            key: 'selected',
            label: '',
            isRowHeader: false,
            sortable: false,
            selectable: false,
            visible: true
        });
    }

    if (!props.selectFields) {
        return fields;
    }

    const visibleFieldsKeysValue = visibleFieldKeys.value;

    return filter(fields, (field) => {
        if (!field.selectable) {
            return true;
        }

        return includes(visibleFieldsKeysValue, field.key);
    });
});

const getPerPageLabel = (num) => {
    return (num === 0) ? 'All' : num.toString();
};

const perPageLabel = computed(() => {
    return getPerPageLabel(perPage.value);
});

const showPagination = computed(() => {
    return props.paginated && perPage.value !== 0;
});

const allSelected = computed(() => {
    return ((selectedRows.value.length === totalRows.value)
        || (showPagination.value && selectedRows.value.length === perPage.value));
});

const {axios} = useAxios();

const loadItems = (ctx) => {
    let queryParams = {
        internal: true
    };

    if (props.handleClientSide) {
        queryParams.rowCount = 0;
    } else {
        if (props.paginated) {
            queryParams.rowCount = ctx.perPage;
            queryParams.current = (ctx.perPage !== 0) ? ctx.currentPage : 1;
        } else {
            queryParams.rowCount = 0;
        }

        if (flushCache.value) {
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
    if (typeof props.requestConfig === 'function') {
        requestConfig = props.requestConfig(requestConfig);
    }

    return axios.get(ctx.apiUrl, requestConfig).then((resp) => {
        totalRows.value = resp.data.total;

        let rows = resp.data.rows;
        if (typeof props.requestProcess === 'function') {
            rows = props.requestProcess(rows);
        }

        return rows;
    }).catch((err) => {
        totalRows.value = 0;

        console.error(err.response.data.message);
        return [];
    }).finally(() => {
        flushCache.value = false;
    });
};

const itemProvider = computed(() => {
    if (props.items !== null) {
        return props.items;
    }

    return (ctx, callback) => {
        return loadItems(ctx, callback);
    }
});

const $table = ref(); // Template Ref

const refresh = () => {
    $table.value?.refresh();
};

const toggleSelected = () => {
    if (allSelected.value) {
        $table.value?.clearSelected();
    } else {
        $table.value?.selectAllRows();
    }
};

const relist = () => {
    flushCache.value = true;
    refresh();
};

const onClickRefresh = (e) => {
    if (e.shiftKey) {
        relist();
    } else {
        refresh();
    }
};

const onRefreshed = () => {
    emit('refreshed');
};

const navigate = () => {
    searchPhrase.value = null;
    currentPage.value = 1;
    relist();
};

const setFilter = (newTerm) => {
    searchPhrase.value = newTerm;
};

const onRowSelected = (items) => {
    selectedRows.value = items;
    emit('row-selected', items);
};

const onFiltered = (filter) => {
    emit('filtered', filter);
};

defineExpose({
    refresh,
    relist,
    navigate,
    setFilter
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
