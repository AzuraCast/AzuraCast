<template>
    <div
        :id="id"
        class="datatable-wrapper"
    >
        <div
            v-if="showToolbar"
            class="datatable-toolbar-top card-body"
        >
            <pagination
                v-model:current-page="currentPage"
                :total="total"
                :per-page="perPage"
                @change="onPageChange"
            />
        </div>
        <div class="grid-main card-body">
            <template v-if="isLoading && hideOnLoading">
                <div
                    class="spinner-border"
                    role="status"
                >
                    <span class="visually-hidden">{{ $gettext('Loading') }}</span>
                </div>
            </template>
            <template v-else-if="visibleItems.length === 0">
                <slot name="empty">
                    {{ $gettext('No records.') }}
                </slot>
            </template>
            <div
                v-else
                class="row view-group"
            >
                <div
                    v-for="(row, index) in visibleItems"
                    :key="index"
                    class="item col-sm-6 col-lg-4"
                >
                    <slot
                        name="item"
                        :item="row"
                    />
                </div>
            </div>
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
import Pagination from "~/components/Common/Pagination.vue";
import {computed, ref, watch} from "vue";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";
import {DataTableFilterContext, DataTableItemProvider, DataTableRow} from "~/functions/useHasDatatable.ts";

export interface GridLayoutProps<Row extends DataTableRow = DataTableRow> {
    id?: string,
    provider: DataTableItemProvider<Row>, // The data provider for this table.
    paginated?: boolean, // Enable pagination.
    hideOnLoading?: boolean, // Replace the contents with a loading animation when data is being retrieved.
    showToolbar?: boolean, // Show the header "Toolbar" with search, refresh, per-page, etc.
    pageOptions?: number[],
    defaultPerPage?: number,
}

const props = withDefaults(defineProps<GridLayoutProps<Row>>(), {
    paginated: false,
    loading: false,
    hideOnLoading: true,
    showToolbar: true,
    pageOptions: () => [10, 25, 50, 100, 250, 500, 0],
    defaultPerPage: 10,
});

const total = computed<number>(() => {
    return props.provider.total.value;
});

const visibleItems = computed<Row[]>(() => {
    return props.provider.rows.value;
});

const isLoading = computed<boolean>(() => {
    return props.provider.loading.value;
});

const currentPage = ref<number>(1);

const settings = useOptionalStorage(
    'grid_' + props.id + '_settings',
    {
        perPage: props.defaultPerPage,
    },
    {
        mergeDefaults: true
    }
);

const perPage = computed<number>(() => {
    if (!props.paginated) {
        return -1;
    }

    return settings.value?.perPage ?? props.defaultPerPage;
});

const context = computed<DataTableFilterContext>(() => {
    return {
        searchPhrase: '',
        currentPage: currentPage.value,
        sortField: null,
        sortOrder: null,
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

watch(perPage, () => {
    currentPage.value = 1;
    relist();
});

defineExpose({
    refresh,
    relist,
});
</script>
