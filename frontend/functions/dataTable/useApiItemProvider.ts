import {
    DATATABLE_DEFAULT_CONTEXT,
    DataTableFilterContext,
    DataTableItemProvider,
    DataTableRow
} from "~/functions/useHasDatatable.ts";
import {MaybeRef, ref, shallowRef, toValue} from "vue";
import {useQuery, UseQueryOptions, UseQueryReturnType} from "@tanstack/vue-query";
import {useQueryItemProvider} from "~/functions/dataTable/useQueryItemProvider.ts";
import {AxiosRequestConfig} from "axios";
import {useAxios} from "~/vendor/axios.ts";

export type ItemProviderResponse<Row extends DataTableRow = DataTableRow> = {
    total: number,
    rows: Row[]
}

export type DataTableQueryItemProvider<Row extends DataTableRow = DataTableRow> = DataTableItemProvider<Row> & {
    query: UseQueryReturnType<ItemProviderResponse<Row>, unknown>
};

export function useApiItemProvider<Row extends DataTableRow = DataTableRow>(
    apiUrl: MaybeRef<string>,
    queryKey: unknown[],
    requestConfigFn?: (config: AxiosRequestConfig) => AxiosRequestConfig,
    requestProcessFn?: (rawData: object[]) => Row[],
    queryOptions?: Partial<UseQueryOptions<ItemProviderResponse<Row>>>
): DataTableQueryItemProvider<Row> {
    const context = shallowRef<DataTableFilterContext>(DATATABLE_DEFAULT_CONTEXT);
    const flushCache = ref<boolean>(false);

    const setContext = (ctx: DataTableFilterContext) => {
        context.value = ctx;
    }

    const compositeQueryKey = queryKey;
    compositeQueryKey.push(context);

    const {axios} = useAxios();

    const query = useQuery({
        queryKey: compositeQueryKey,
        queryFn: async (ctx) => {
            console.log(ctx);

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
            }

            if (context.value.searchPhrase !== '') {
                queryParams.searchPhrase = context.value.searchPhrase;
            }

            if (null !== context.value.sortField) {
                queryParams.sort = context.value.sortField;
                queryParams.sortOrder = (context.value.sortOrder === 'desc') ? 'DESC' : 'ASC';
            }

            let requestConfig: AxiosRequestConfig = {params: queryParams};
            if (typeof requestConfigFn === 'function') {
                requestConfig = requestConfigFn(requestConfig);
            }

            const {data} = await axios.get<ItemProviderResponse<Row>>(
                toValue(apiUrl),
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
        },
        ...queryOptions
    });

    const refresh = async (flush: boolean): Promise<void> => {
        if (flush) {
            flushCache.value = true;
            await query.refetch();
            flushCache.value = false;
        } else {
            await query.refetch();
        }
    }

    return useQueryItemProvider(
        query,
        setContext,
        refresh
    );
}
