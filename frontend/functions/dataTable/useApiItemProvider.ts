import {
    DATATABLE_DEFAULT_CONTEXT,
    DataTableFilterContext,
    DataTableItemProvider,
    DataTableRow
} from "~/functions/useHasDatatable.ts";
import {computed, MaybeRef, ref, shallowRef, toValue} from "vue";
import {
    DefaultError,
    keepPreviousData,
    QueryFunction,
    useQuery,
    useQueryClient,
    UseQueryOptions,
    UseQueryReturnType
} from "@tanstack/vue-query";
import {AxiosRequestConfig} from "axios";
import {useAxios} from "~/vendor/axios.ts";

export type ItemProviderResponse<Row extends DataTableRow = DataTableRow> = {
    total: number,
    rows: Row[]
}

export type DataTableApiItemProvider<Row extends DataTableRow = DataTableRow> = DataTableItemProvider<Row> & {
    query: UseQueryReturnType<ItemProviderResponse<Row>, DefaultError>,
    prefetch: () => Promise<void>
};

export function useApiItemProvider<Row extends DataTableRow = DataTableRow>(
    apiUrl: MaybeRef<string | null>,
    queryKey: unknown[],
    queryOptions?: Partial<UseQueryOptions<ItemProviderResponse<Row>>>,
    requestConfigFn?: (config: AxiosRequestConfig) => AxiosRequestConfig,
    requestProcessFn?: (rawData: object[]) => Row[],
): DataTableApiItemProvider<Row> {
    const context = shallowRef<DataTableFilterContext>({
        ...DATATABLE_DEFAULT_CONTEXT,
        paginated: true,
        perPage: 10
    });
    const flushCache = ref<boolean>(false);

    const setContext = (ctx: DataTableFilterContext) => {
        context.value = ctx;
    }

    const compositeQueryKey = queryKey;
    compositeQueryKey.push(context);

    const {axios} = useAxios();

    const queryFn: QueryFunction<ItemProviderResponse<Row>> = async ({signal}) => {
        const currentApiUrl = toValue(apiUrl);
        if (currentApiUrl === null) {
            return {
                total: 0,
                rows: [],
            };
        }

        const queryParams: {
            [key: string]: any
        } = {
            internal: true
        };

        if (context.value.paginated) {
            queryParams.rowCount = context.value.perPage;
            queryParams.current = (context.value.perPage !== 0) ? context.value.currentPage : 1;
        } else {
            queryParams.rowCount = 0;
        }

        if (flushCache.value) {
            queryParams.flushCache = true;
            flushCache.value = false;
        }

        if (context.value.searchPhrase !== '') {
            queryParams.searchPhrase = context.value.searchPhrase;
        }

        if (null !== context.value.sortField) {
            queryParams.sort = context.value.sortField;
            queryParams.sortOrder = (context.value.sortOrder === 'desc') ? 'DESC' : 'ASC';
        }

        let requestConfig: AxiosRequestConfig = {
            params: queryParams,
            signal
        };

        if (typeof requestConfigFn === 'function') {
            requestConfig = requestConfigFn(requestConfig);
        }

        const {data} = await axios.get<ItemProviderResponse<Row>>(
            currentApiUrl,
            requestConfig
        );

        let rows = data.rows ?? [];
        if (typeof requestProcessFn === 'function') {
            rows = requestProcessFn(rows);
        }

        return {
            total: data.total,
            rows: rows,
        };
    };

    const query = useQuery<ItemProviderResponse<Row>>({
        queryKey: compositeQueryKey,
        queryFn,
        staleTime: 30 * 1000,
        placeholderData: keepPreviousData,
        enabled: computed(() => toValue(apiUrl) !== null),
        ...queryOptions
    });

    const rows = computed(() => {
        if (query.data.value) {
            return query.data.value.rows;
        }

        return [];
    });

    const total = computed(() => {
        if (query.data.value) {
            return query.data.value.total;
        }

        return 0;
    });

    const loading = computed<boolean>(() => {
        return query.isFetching.value;
    });

    const queryClient = useQueryClient();

    const refresh = async (flush?: boolean): Promise<void> => {
        if (flush) {
            flushCache.value = true;
        }

        await queryClient.invalidateQueries({
            queryKey: queryKey
        });
    }

    const prefetch = async (): Promise<void> => {
        await queryClient.prefetchQuery({
            queryKey: queryKey,
            queryFn
        });
    }

    return {
        query,
        rows,
        total,
        loading,
        setContext,
        refresh,
        prefetch
    };
}
