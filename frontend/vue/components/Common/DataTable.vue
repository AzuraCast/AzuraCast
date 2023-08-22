<template>
    <div
        :id="id"
        style="display: contents"
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
                        :total="totalRows"
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
                                <icon icon="search" />
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
                                <icon icon="refresh" />
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
                                    <icon icon="filter_list" />
                                    <span class="caret" />
                                </button>
                                <div class="dropdown-menu">
                                    <div class="px-3 py-1">
                                        <form-multi-check
                                            id="field_select"
                                            v-model="settings.visibleFieldKeys"
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
                (responsive) ? 'table-responsive' : ''
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
                            @click.stop="sort(column, null, $event)"
                        >
                            <slot
                                :name="'header('+column.key+')'"
                                v-bind="column"
                            >
                                <div class="d-flex align-items-center">
                                    {{ column.label }}

                                    <template v-if="column.sortable && sortField === column.key">
                                        <icon :icon="(sortOrder === 'asc') ? 'arrow_drop_down' : 'arrow_drop_up'" />
                                    </template>
                                </div>
                            </slot>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="loading">
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
                                {{ $gettext('No records.') }}
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
                                    :name="'cell('+column.key+')'"
                                    :column="column"
                                    :item="row"
                                    :toggle-details="() => toggleDetails(row)"
                                >
                                    <template v-if="column.formatter">
                                        {{ column.formatter(get(row, column.key, null), column.key, row) }}
                                    </template>
                                    <template v-else>
                                        {{ get(row, column.key, null) }}
                                    </template>
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

                    <tr v-if="!visibleItems.length">
                        <td :colspan="columnCount">
                            <slot name="empty" />
                        </td>
                    </tr>
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
                :total="totalRows"
                :per-page="perPage"
                @change="onPageChange"
            />
        </div>
    </div>
</template>

<script setup>
import {slice, filter, map, includes, isEmpty, get, some, indexOf, forEach} from 'lodash';
import Icon from './Icon.vue';
import {computed, onMounted, ref, shallowRef, toRaw, toRef, useSlots, watch} from "vue";
import {watchDebounced} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import FormCheckbox from "~/components/Form/FormCheckbox.vue";
import Pagination from "~/components/Common/Pagination.vue";
import useOptionalStorage from "~/functions/useOptionalStorage";

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
    detailed: {
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

const slots = useSlots();

const emit = defineEmits([
    'refreshed',
    'row-selected',
    'filtered',
    'data-loaded'
]);

const selectedRows = shallowRef([]);

const searchPhrase = ref('');
const currentPage = ref(1);
const flushCache = ref(false);

const sortField = ref(null);
const sortOrder = ref(null);

const loading = ref(false);
const allItems = shallowRef([]);
const totalRows = ref(0);

const activeDetailsRow = shallowRef(null);

watch(toRef(props, 'items'), (newVal) => {
    if (newVal !== null) {
        allItems.value = toRaw(newVal);
        totalRows.value = allItems.value.length;
    }
}, {
    immediate: true
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
            class: null,
            ...field
        };
    });
});

const selectableFields = computed(() => {
    return filter({...allFields.value}, (field) => {
        return field.selectable;
    });
});

const selectableFieldOptions = computed(() => map(selectableFields.value, (field) => {
    return {
        value: field.key,
        text: field.label
    };
}));

const defaultSelectableFields = computed(() => {
    return filter({...selectableFields.value}, (field) => {
        return field.visible;
    });
});

const settings = useOptionalStorage(
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
    if (!props.paginated) {
        return -1;
    }

    return settings.value?.perPage ?? props.defaultPerPage;
});

const visibleFields = computed(() => {
    const fields = allFields.value.slice();

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

const visibleItems = computed(() => {
    if (!props.handleClientSide) {
        return allItems.value;
    }

    // Handle pagination client-side.
    let itemsOnPage;

    if (props.paginated && perPage.value > 0) {
        itemsOnPage = slice(
            allItems.value,
            (currentPage.value - 1) * perPage.value,
            currentPage.value * perPage.value
        );
    } else {
        itemsOnPage = allItems.value;
    }

    // Handle filtration client-side.
    return filter(itemsOnPage, (item) =>
        Object.entries(item).filter((item) => {
            const [key, val] = item;
            if (!val || key[0] === '_') {
                return false;
            }

            const itemValue = typeof val === 'object'
                ? JSON.stringify(Object.values(val))
                : typeof val === 'string'
                    ? val : val.toString();

            return itemValue.toLowerCase().includes(searchPhrase.value.toLowerCase())
        }).length > 0
    );
});

const {axios} = useAxios();

const refresh = () => {
    selectedRows.value = [];
    activeDetailsRow.value = null;
    
    if (props.items !== null) {
        emit('refreshed');
        return;
    }

    const queryParams = {
        internal: true
    };

    if (props.handleClientSide) {
        queryParams.rowCount = 0;
    } else {
        if (props.paginated) {
            queryParams.rowCount = perPage.value;
            queryParams.current = (perPage.value !== 0) ? currentPage.value : 1;
        } else {
            queryParams.rowCount = 0;
        }

        if (flushCache.value) {
            queryParams.flushCache = true;
        }

        if (searchPhrase.value !== '') {
            queryParams.searchPhrase = searchPhrase.value;
        }

        if ('' !== sortField.value) {
            queryParams.sort = sortField.value;
            queryParams.sortOrder = (sortOrder.value === 'desc') ? 'DESC' : 'ASC';
        }
    }

    let requestConfig = {params: queryParams};
    if (typeof props.requestConfig === 'function') {
        requestConfig = props.requestConfig(requestConfig);
    }

    loading.value = true;

    return axios.get(props.apiUrl, requestConfig).then((resp) => {
        totalRows.value = resp.data.total;

        let rows = resp.data.rows;
        if (typeof props.requestProcess === 'function') {
            rows = props.requestProcess(rows);
        }

        emit('data-loaded', rows);
        allItems.value = rows;
    }).catch((err) => {
        totalRows.value = 0;

        console.error(err.response.data.message);
    }).finally(() => {
        loading.value = false;
        flushCache.value = false;
        emit('refreshed');
    });
};

const onPageChange = (p) => {
    currentPage.value = p;
    refresh();
}

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

const navigate = () => {
    searchPhrase.value = null;
    currentPage.value = 1;
    relist();
};

const setFilter = (newTerm) => {
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

onMounted(refresh);

const isAllChecked = computed(() => {
    if (visibleItems.value.length === 0) {
        return false;
    }

    return !some(visibleItems.value, (currentVisibleRow) => {
        return indexOf(selectedRows.value, currentVisibleRow) < 0;
    });
});

const isRowChecked = (row) => {
    return indexOf(selectedRows.value, row) >= 0;
}

const columnCount = computed(() => {
    let count = visibleFields.value.length;
    count += props.selectable ? 1 : 0;
    return count
});

const sort = (column) => {
    if (!column.sortable) {
        return;
    }

    if (sortField.value === column.key) {
        sortOrder.value = (sortOrder.value === 'asc')
            ? 'desc'
            : 'asc';
    } else {
        sortOrder.value = 'asc';
    }

    sortField.value = column.key;
    refresh();
};

const checkRow = (row) => {
    const newSelectedRows = selectedRows.value;

    if (isRowChecked(row)) {
        const index = indexOf(newSelectedRows, row);
        if (index >= 0) {
            newSelectedRows.splice(index, 1);
        }
    } else {
        newSelectedRows.push(row);
    }

    emit('row-selected', newSelectedRows);
    selectedRows.value = newSelectedRows;
}

const checkAll = () => {
    const newSelectedRows = [];

    if (!isAllChecked.value) {
        forEach(visibleItems.value, (currentRow) => {
            newSelectedRows.push(currentRow);
        });
    }

    emit('row-selected', newSelectedRows);
    selectedRows.value = newSelectedRows;
};

const isActiveDetailRow = (row) => {
    return activeDetailsRow.value === row;
};

const toggleDetails = (row) => {
    activeDetailsRow.value = isActiveDetailRow(row)
        ? null
        : row;
};

defineExpose({
    refresh,
    relist,
    navigate,
    setFilter,
    toggleDetails
});
</script>
