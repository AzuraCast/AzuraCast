<template>
    <div
        :id="id"
        class="datatable-wrapper"
    >
        <div
            v-if="showToolbar"
            class="datatable-toolbar-top card-body"
        >
            <div class="row align-items-center">
                <div
                    v-if="showPagination"
                    class="col-xl-6 col-lg-5 col-md-12 col-sm-12"
                >
                    <pagination
                        v-model:current-page="currentPage"
                        :total="total"
                        :per-page="perPage"
                        @change="onPageChange"
                    />
                </div>
                <div
                    v-else
                    class="col-xl-6 col-lg-5 col-md-12 col-sm-12"
                >
                    &nbsp;
                </div>
                <div
                    class="col-xl-6 col-lg-7 col-md-12 col-sm-12 d-flex my-2"
                >
                    <div class="flex-fill">
                        <div class="input-group">
                            <span class="input-group-text">
                                <icon-ic-search/>
                            </span>
                            <input
                                v-model="searchPhrase"
                                class="form-control"
                                type="search"
                                :placeholder="$gettext('Search')"
                            >
                        </div>
                    </div>
                    <div class="flex-shrink-1 ps-3">
                        <div class="btn-group actions">
                            <button
                                type="button"
                                data-bs-tooltip
                                class="btn btn-secondary"
                                data-bs-placement="left"
                                :title="$gettext('Refresh rows')"
                                @click="onClickRefresh"
                            >
                                <icon-ic-refresh/>
                            </button>

                            <div
                                v-if="paginated"
                                class="dropdown btn-group"
                                role="group"
                            >
                                <button
                                    type="button"
                                    data-bs-tooltip
                                    class="btn btn-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    data-bs-placement="left"
                                    :title="$gettext('Items per page')"
                                >
                                    <span>
                                        {{ perPageLabel }}
                                    </span>
                                    <span class="caret" />
                                </button>
                                <ul class="dropdown-menu">
                                    <li
                                        v-for="pageOption in pageOptions"
                                        :key="pageOption"
                                    >
                                        <button
                                            type="button"
                                            class="dropdown-item"
                                            :class="(pageOption === perPage) ? 'active' : ''"
                                            @click="settings.perPage = pageOption"
                                        >
                                            {{ getPerPageLabel(pageOption) }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-if="selectFields"
                                class="dropdown btn-group"
                                role="group"
                            >
                                <button
                                    type="button"
                                    class="btn btn-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    data-bs-placement="left"
                                    :title="$gettext('Display fields')"
                                >
                                    <icon-ic-filter-list/>
                                    <span class="caret" />
                                </button>
                                <div class="dropdown-menu">
                                    <div class="px-3 py-1">
                                        <form-multi-check
                                            id="field_select"
                                            v-model="visibleFieldKeys"
                                            :options="selectableFieldOptions"
                                            stacked
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="datatable-main"
            :class="[
                responsiveClass
            ]"
        >
            <table
                class="table align-middle table-striped table-hover"
                :class="[
                    (selectable) ? 'table-selectable' : ''
                ]"
            >
                <caption v-if="slots.caption">
                    <slot name="caption" />
                </caption>
                <thead>
                    <tr>
                        <th
                            v-if="selectable"
                            class="checkbox"
                        >
                            <label class="form-check">
                                <form-checkbox
                                    autocomplete="off"
                                    :model-value="isAllChecked"
                                    @update:model-value="checkAll"
                                />
                                <span class="visually-hidden">
                                    <template v-if="isAllChecked">
                                        {{ $gettext('Unselect All Rows') }}
                                    </template>
                                    <template v-else>
                                        {{ $gettext('Select All Rows') }}
                                    </template>
                                </span>
                            </label>
                        </th>
                        <th
                            v-for="(column) in visibleFields"
                            :key="column.key+'header'"
                            :class="[
                                column.class,
                                (column.sortable) ? 'sortable' : ''
                            ]"
                            :aria-sort="(column.sortable && sortField?.key === column.key)
                                ? ((sortOrder === 'asc') ? 'ascending' : 'descending')
                                : undefined"
                            @click.stop="sort(column)"
                        >
                            <slot
                                :name="`header(${column.key})`"
                                v-bind="column"
                            >
                                <div class="d-flex align-items-center">
                                    {{ column.label }}

                                    <template v-if="column.sortable && sortField?.key === column.key">
                                        <icon-ic-arrow-drop-up v-if="sortOrder === 'asc'"/>
                                        <icon-ic-arrow-drop-down v-else/>
                                    </template>
                                </div>
                            </slot>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="isLoading && hideOnLoading">
                        <tr>
                            <td
                                :colspan="columnCount"
                                class="text-center p-5"
                            >
                                <div
                                    class="spinner-border"
                                    role="status"
                                >
                                    <span class="visually-hidden">{{ $gettext('Loading') }}</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="visibleItems.length === 0">
                        <tr>
                            <td :colspan="columnCount">
                                <slot name="empty">
                                    {{ $gettext('No records.') }}
                                </slot>
                            </td>
                        </tr>
                    </template>
                    <template
                        v-for="(row, index) in visibleItems"
                        v-else
                        :key="index"
                    >
                        <tr
                            :class="[
                                isActiveDetailRow(row) ? 'table-active' : ''
                            ]"
                        >
                            <td
                                v-if="selectable"
                                class="checkbox"
                            >
                                <label class="form-check">
                                    <form-checkbox
                                        autocomplete="off"
                                        :model-value="isRowChecked(row)"
                                        @update:model-value="checkRow(row)"
                                    />
                                    <span class="visually-hidden">
                                        <template v-if="isRowChecked(row)">
                                            {{ $gettext('Unselect Row') }}
                                        </template>
                                        <template v-else>
                                            {{ $gettext('Select Row') }}
                                        </template>
                                    </span>
                                </label>
                            </td>
                            <td
                                v-for="column in visibleFields"
                                :key="column.key+':'+index"
                                :class="column.class"
                            >
                                <slot
                                    :name="`cell(${column.key})`"
                                    :column="column"
                                    :item="row"
                                    :is-active="isActiveDetailRow(row)"
                                    :toggle-details="() => toggleDetails(row)"
                                >
                                    {{ getColumnValue(column, row) }}
                                </slot>
                            </td>
                        </tr>
                        <tr
                            v-if="isActiveDetailRow(row)"
                            :key="index+':detail'"
                            class="table-active"
                        >
                            <td :colspan="columnCount">
                                <slot
                                    name="detail"
                                    :item="row"
                                    :index="index"
                                />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div
            v-if="showToolbar"
            class="datatable-toolbar-bottom card-body"
        >
            <pagination
                v-if="showPagination"
                v-model:current-page="currentPage"
                :total="total"
                :per-page="perPage"
                @change="onPageChange"
            />
        </div>
    </div>
</template>

<script setup lang="ts" generic="Row extends DataTableRow = DataTableRow">
import {filter, forEach, get, isEmpty, some} from "es-toolkit/compat";
import {computed, ref, shallowRef, toRaw, watch} from "vue";
import {watchDebounced} from "@vueuse/core";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import FormCheckbox from "~/components/Form/FormCheckbox.vue";
import Pagination from "~/components/Common/Pagination.vue";
import useOptionalStorage from "~/functions/useOptionalStorage";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {
    DATATABLE_DEFAULT_CONTEXT,
    DataTableFilterContext,
    DataTableItemProvider,
    DataTableRow
} from "~/functions/useHasDatatable.ts";
import {isString} from "es-toolkit";
import IconIcArrowDropUp from "~icons/ic/baseline-arrow-drop-up";
import IconIcArrowDropDown from "~icons/ic/baseline-arrow-drop-down";
import IconIcFilterList from "~icons/ic/baseline-filter-list";
import IconIcRefresh from "~icons/ic/baseline-refresh";
import IconIcSearch from "~icons/ic/baseline-search";

export interface DataTableField<Row extends DataTableRow = DataTableRow> {
    key: string,
    label: string,
    isRowHeader?: boolean,
    sortable?: boolean,
    selectable?: boolean,
    visible?: boolean,
    "class"?: string | Array<any>,

    formatter?(value: any, key: string, row: Row): string,

    sorter?(row: Row): string
}

export interface DataTableProps<Row extends DataTableRow = DataTableRow> {
    id?: string,
    fields: DataTableField<Row>[],
    provider: DataTableItemProvider<Row>, // The data provider for this table.
    responsive?: boolean | string, // Make table responsive (boolean or CSS class for specific responsiveness width)
    paginated?: boolean, // Enable pagination.
    hideOnLoading?: boolean, // Replace the table contents with a loading animation when data is being retrieved.
    showToolbar?: boolean, // Show the header "Toolbar" with search, refresh, per-page, etc.
    pageOptions?: number[],
    defaultPerPage?: number,
    selectable?: boolean, // Allow selecting individual rows with checkboxes at the side of each row
    detailed?: boolean, // Allow showing "Detail" panel for selected rows.
    selectFields?: boolean, // Allow selecting which columns are visible.
}

const props = withDefaults(defineProps<DataTableProps<Row>>(), {
    responsive: () => true,
    paginated: DATATABLE_DEFAULT_CONTEXT.paginated,
    hideOnLoading: true,
    showToolbar: true,
    pageOptions: () => [10, 25, 50, 100, 250, 500, 0],
    defaultPerPage: DATATABLE_DEFAULT_CONTEXT.perPage,
    selectable: false,
    detailed: false,
    selectFields: false
});

const slots = defineSlots<{
    [key: `header(${string})`]: (props: DataTableField<Row>) => any,
    [key: `cell(${string})`]: (props: {
        column: DataTableField<Row>,
        item: Row,
        isActive: boolean,
        toggleDetails: () => void
    }) => any,
    'detail'?: (props: {
        item: Row,
        index: number
    }) => any,
    'caption'?: () => any,
    'empty'?: () => any,
}>();

const emit = defineEmits<{
    (e: 'row-selected', rows: Row[]): void,
    (e: 'filtered', newPhrase: string): void,
}>();

const total = computed<number>(() => {
    return props.provider.total.value;
});

const visibleItems = computed<Row[]>(() => {
    return props.provider.rows.value;
});

const isLoading = computed<boolean>(() => {
    return props.provider.loading.value;
});

const selectedRows = shallowRef<Row[]>([]);

watch(selectedRows, (newRows: Row[]) => {
    emit('row-selected', newRows);
});

const searchPhrase = ref<string>(DATATABLE_DEFAULT_CONTEXT.searchPhrase);
const currentPage = ref<number>(DATATABLE_DEFAULT_CONTEXT.currentPage);

const sortField = ref<DataTableField<Row> | null>(null);
const sortOrder = ref<string | null>(null);

const activeDetailsRow = shallowRef<Row | null>(null);

watch(visibleItems, () => {
    selectedRows.value = [];
    activeDetailsRow.value = null;
});

type RowField = DataTableField<Row>
type RowFields = RowField[]

const allFields = computed<RowFields>(() => {
    return props.fields.map((field: RowField): RowField => {
        return {
            isRowHeader: false,
            sortable: false,
            selectable: false,
            visible: true,
            class: undefined,
            formatter: undefined,
            sorter: (row: Row): string => get(row, field.key),
            ...field
        };
    });
});

const selectableFields = computed<RowFields>(() => {
    return filter({...allFields.value}, (field: RowField) => {
        return field.selectable ?? false;
    });
});

const selectableFieldOptions = computed<SimpleFormOptionInput>(() => selectableFields.value.map((field) => {
    return {
        value: field.key,
        text: field.label
    };
}));

const defaultSelectableFields = computed<RowFields>(() => {
    return filter({...selectableFields.value}, (field: RowField) => {
        return field.visible ?? true;
    });
});

const settings = useOptionalStorage(
    'datatable_' + props.id + '_settings',
    {
        sortBy: null,
        sortDesc: false,
        perPage: props.defaultPerPage,
        visibleFieldKeys: defaultSelectableFields.value.map((field) => field.key),
    },
    {
        mergeDefaults: true
    }
);

const visibleFieldKeys = computed({
    get: () => {
        const settingsKeys = toRaw(settings.value.visibleFieldKeys);
        if (!isEmpty(settingsKeys)) {
            return settingsKeys;
        }

        return defaultSelectableFields.value.map((field) => field.key);
    },
    set: (newValue) => {
        if (isEmpty(newValue)) {
            newValue = defaultSelectableFields.value.map((field) => field.key);
        }

        settings.value.visibleFieldKeys = newValue;
    }
});

const perPage = computed<number>(() => {
    if (!props.paginated) {
        return -1;
    }

    return settings.value?.perPage ?? props.defaultPerPage;
});

const context = computed<DataTableFilterContext>(() => {
    return {
        searchPhrase: searchPhrase.value,
        currentPage: currentPage.value,
        sortField: sortField.value?.key ?? null,
        sortOrder: sortOrder.value,
        paginated: props.paginated,
        perPage: perPage.value,
    };
});

watch(
    context,
    (newContext) => {
        props.provider.setContext(newContext);
    },
    {
        immediate: true
    }
);

const visibleFields = computed<DataTableField<Row>[]>(() => {
    const fields = allFields.value.slice();

    if (!props.selectFields) {
        return fields;
    }

    const visibleFieldsKeysValue = visibleFieldKeys.value;

    return filter(fields, (field) => {
        if (!field.selectable) {
            return true;
        }

        return visibleFieldsKeysValue.indexOf(field.key) !== -1;
    });
});

const getPerPageLabel = (num: number): string => {
    return (num === 0) ? 'All' : num.toString();
};

const perPageLabel = computed<string>(() => {
    return getPerPageLabel(perPage.value);
});

const showPagination = computed<boolean>(() => {
    return props.paginated && perPage.value !== 0;
});

const doRefresh = async (flushCache: boolean = false): Promise<void> => {
    await props.provider.refresh(flushCache);
}

const refresh = () => {
    void doRefresh(false);
};

const onPageChange = (p: number) => {
    currentPage.value = p;
}

const relist = () => {
    void doRefresh(true);
};

const onClickRefresh = (e: MouseEvent) => {
    void doRefresh(e.shiftKey);
};

const navigate = () => {
    searchPhrase.value = '';
    currentPage.value = 1;
};

const setFilter = (newTerm: string) => {
    searchPhrase.value = newTerm;
};

watch(perPage, () => {
    currentPage.value = 1;
    relist();
});

watchDebounced(searchPhrase, (newSearchPhrase) => {
    currentPage.value = 1;
    relist();

    emit('filtered', newSearchPhrase);
}, {
    debounce: 500,
    maxWait: 1000
});

const isAllChecked = computed<boolean>(() => {
    if (visibleItems.value.length === 0) {
        return false;
    }

    return !some(visibleItems.value, (currentVisibleRow) => {
        return selectedRows.value.indexOf(currentVisibleRow) === -1;
    });
});

const isRowChecked = (row: Row) => {
    return selectedRows.value.indexOf(row) !== -1;
};

const columnCount = computed(() => {
    let count = visibleFields.value.length;
    count += props.selectable ? 1 : 0;
    return count
});

const sort = (column: DataTableField) => {
    if (!column.sortable) {
        return;
    }

    if (sortField.value?.key === column.key && sortOrder.value === 'desc') {
        sortOrder.value = null;
        sortField.value = null;
    } else {
        sortOrder.value = (sortField.value?.key === column.key)
            ? 'desc'
            : 'asc';
        sortField.value = column;
    }

    refresh();
};

const checkRow = (row: Row) => {
    const newSelectedRows = selectedRows.value.slice();

    if (isRowChecked(row)) {
        const index = newSelectedRows.indexOf(row);
        if (index >= 0) {
            newSelectedRows.splice(index, 1);
        }
    } else {
        newSelectedRows.push(row);
    }

    selectedRows.value = newSelectedRows;
}

const checkAll = () => {
    const newSelectedRows: Row[] = [];

    if (!isAllChecked.value) {
        forEach(visibleItems.value, (currentRow) => {
            newSelectedRows.push(currentRow);
        });
    }

    selectedRows.value = newSelectedRows;
};

const isActiveDetailRow = (row: Row) => {
    return activeDetailsRow.value === row;
};

const toggleDetails = (row: Row) => {
    activeDetailsRow.value = isActiveDetailRow(row)
        ? null
        : row;
};

const responsiveClass = computed(() => {
    if (isString(props.responsive)) {
        return props.responsive;
    }

    return (props.responsive ? 'table-responsive' : '');
});

const getColumnValue = (field: DataTableField<Row>, row: Row): string => {
    const columnValue = get(row, field.key, '');

    return (field.formatter)
        ? field.formatter(columnValue, field.key, row)
        : columnValue;
}

defineExpose({
    refresh,
    relist,
    navigate,
    setFilter,
    toggleDetails
});
</script>
