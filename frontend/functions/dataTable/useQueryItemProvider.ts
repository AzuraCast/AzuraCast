import {DataTableFilterContext, DataTableItemProvider, DataTableRow} from "~/functions/useHasDatatable.ts";
import {computed} from "vue";
import {DefaultError, UseQueryReturnType} from "@tanstack/vue-query";

export type ItemProviderResponse<Row extends DataTableRow = DataTableRow> = {
    total: number,
    rows: Row[]
}

export type DataTableQueryItemProvider<Row extends DataTableRow = DataTableRow> = DataTableItemProvider<Row> & {
    query: UseQueryReturnType<ItemProviderResponse<Row>, DefaultError>
};

export function useQueryItemProvider<Row extends DataTableRow = DataTableRow>(
    query: UseQueryReturnType<ItemProviderResponse<Row>, DefaultError>,
    setContextFn?: (ctx: DataTableFilterContext) => void,
    refreshFn?: (flushCache: boolean) => Promise<void>
): DataTableQueryItemProvider<Row> {
    const rows = computed(() => {
        return query.data?.value?.rows ?? [];
    });

    const total = computed(() => {
        return query.data?.value?.total ?? 0;
    });

    const loading = computed<boolean>(() => {
        return query.isLoading.value;
    });

    const setContext = (ctx: DataTableFilterContext): void => {
        if (typeof setContextFn === 'function') {
            setContextFn(ctx);
        }
    }

    const refresh = async (flushCache: boolean): Promise<void> => {
        if (typeof refreshFn === 'function') {
            await refreshFn(flushCache);
        } else {
            await query.refetch();
        }
    }

    return {
        query,
        rows,
        total,
        loading,
        setContext,
        refresh
    }
}
