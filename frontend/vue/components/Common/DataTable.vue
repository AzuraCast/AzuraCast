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
                    <o-pagination
                        v-if="showPagination"
                        v-model:current="currentPage"
                        :total="totalRows"
                        :per-page="perPage"
                        icon-prev="chevron-left"
                        icon-next="chevron-right"
                        :aria-next-label="$gettext('Next page')"
                        :aria-previous-label="$gettext('Previous page')"
                        :aria-page-label="$gettext('Page')"
                        :aria-current-label="$gettext('Current page')"
                        class="mb-0"
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
        <div class="datatable-main">
            <o-table
                ref="$table"
                v-model:checked-rows="selectedRows"
                striped
                :mobile-cards="false"
                :table-class="[
                    'align-middle',
                    (responsive) ? 'table-responsive' : '',
                    (selectable) ? 'table-selectable' : ''
                ]"

                hoverable
                :data="visibleItems"

                :loading="loading"
                :checkable="selectable"
                checkbox-position="left"

                :paginated="false"

                :backend-sorting="!handleClientSide"

                @page-change="onPageChange"
                @sort="onSort"
            >
                <o-table-column
                    v-for="field in visibleFields"
                    :key="field.key"
                    v-slot="{ row }"
                    :field="field.key"
                    :label="field.label"
                    :sortable="field.sortable"
                    :th-attrs="() => ({class: field.class})"
                    :td-attrs="() => ({class: field.class})"
                >
                    <slot
                        :name="'cell('+field.key+')'"
                        v-bind="{item: row}"
                    >
                        <template v-if="field.formatter">
                            {{ field.formatter(get(row, field.key, null), field.key, row) }}
                        </template>
                        <template v-else>
                            {{ get(row, field.key, null) }}
                        </template>
                    </slot>
                </o-table-column>
            </o-table>
        </div>
        <div
            v-if="showToolbar"
            class="datatable-toolbar-bottom card-body"
        >
            <o-pagination
                v-if="showPagination"
                v-model:current="currentPage"
                :total="totalRows"
                :per-page="perPage"
                class="mb-0"
                @change="onPageChange"
            />
        </div>
    </div>
</template>

<script setup>
import {slice, filter, map, includes, isEmpty, get} from 'lodash';
import Icon from './Icon.vue';
import {computed, onMounted, ref, toRef, watch} from "vue";
import {useLocalStorage, watchDebounced} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";

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

watch(selectedRows, (rows) => {
    emit('row-selected', rows);
});

const searchPhrase = ref('');
const currentPage = ref(1);
const flushCache = ref(false);

const sortField = ref(null);
const sortOrder = ref(null);

const loading = ref(false);
const items = ref(props.items ?? []);
const totalRows = ref(items.value.length);

watch(toRef(props, 'items'), (newVal) => {
    if (newVal !== null) {
        items.value = newVal;
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
    if (!props.paginated) {
        return -1;
    }

    return settings.value?.perPage ?? props.defaultPerPage;
});

const visibleFields = computed(() => {
    let fields = allFields.value.slice();

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
        return items.value;
    }

    // Handle pagination client-side.
    let itemsOnPage;

    if (props.paginated && perPage.value > 0) {
        itemsOnPage = slice(
            items.value,
            (currentPage.value - 1) * perPage.value,
            currentPage.value * perPage.value
        );
    } else {
        itemsOnPage = items.value;
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
    
    if (props.items !== null) {
        emit('refreshed');
        return;
    }

    let queryParams = {
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

        items.value = rows;
    }).catch((err) => {
        totalRows.value = 0;

        console.error(err.response.data.message);
    }).finally(() => {
        loading.value = false;
        flushCache.value = false;
        emit('refreshed');
    });
};

const $table = ref(); // Template Ref

const onSort = (field, order) => {
    sortField.value = field;
    sortOrder.value = order;
    refresh();
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

defineExpose({
    refresh,
    relist,
    navigate,
    setFilter
});
</script>
