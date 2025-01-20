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
                :total="totalRows"
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
                :total="totalRows"
                :per-page="perPage"
                @change="onPageChange"
            />
        </div>
    </div>
</template>

<script setup lang="ts" generic="Row extends object">
import Pagination from "~/components/Common/Pagination.vue";
import {useAxios} from "~/vendor/axios.ts";
import {computed, onMounted, ref, shallowRef, toRef, watch} from "vue";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";

export interface GridLayoutProps {
    id?: string,
    apiUrl?: string, // URL to fetch for server-side data
    paginated?: boolean, // Enable pagination.
    loading?: boolean, // Pass to override the "loading" property for this grid.
    hideOnLoading?: boolean, // Replace the contents with a loading animation when data is being retrieved.
    showToolbar?: boolean, // Show the header "Toolbar" with search, refresh, per-page, etc.
    pageOptions?: number[],
    defaultPerPage?: number,
}

const props = withDefaults(defineProps<GridLayoutProps>(), {
    paginated: false,
    loading: false,
    hideOnLoading: true,
    showToolbar: true,
    pageOptions: () => [10, 25, 50, 100, 250, 500, 0],
    defaultPerPage: 10,
});

const emit = defineEmits([
    'refreshed',
    'data-loaded'
]);

const currentPage = ref<number>(1);
const flushCache = ref<boolean>(false);
const isLoading = ref<boolean>(false);

watch(toRef(props, 'loading'), (newLoading: boolean) => {
    isLoading.value = newLoading;
});

const visibleItems = shallowRef<Row[]>([]);
const totalRows = ref(0);

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

const showPagination = computed<boolean>(() => {
    return props.paginated && perPage.value !== 0;
});

const {axios} = useAxios();

const refresh = () => {
    const queryParams: {
        [key: string]: any
    } = {
        internal: true
    };

    if (props.paginated) {
        queryParams.rowCount = perPage.value;
        queryParams.current = (perPage.value !== 0) ? currentPage.value : 1;
    } else {
        queryParams.rowCount = 0;
    }

    if (flushCache.value) {
        queryParams.flushCache = true;
    }

    isLoading.value = true;

    return axios.get(props.apiUrl, {params: queryParams}).then((resp) => {
        totalRows.value = resp.data.total;

        const rows = resp.data.rows;

        emit('data-loaded', rows);
        visibleItems.value = rows;
    }).catch((err) => {
        totalRows.value = 0;
        console.error(err.response.data.message);
    }).finally(() => {
        isLoading.value = false;
        flushCache.value = false;
        emit('refreshed');
    });
}

const onPageChange = (p) => {
    currentPage.value = p;
    refresh();
}

const relist = () => {
    flushCache.value = true;
    refresh();
};

watch(perPage, () => {
    currentPage.value = 1;
    relist();
});

onMounted(refresh);

defineExpose({
    refresh,
    relist,
});
</script>
