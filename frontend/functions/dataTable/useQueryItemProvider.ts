import {DataTableFilterContext, DataTableItemProvider, DataTableRow} from "~/functions/useHasDatatable.ts";
import {computed} from "vue";
import {DefaultError, UseQueryReturnType} from "@tanstack/vue-query";
import {useClientItemProvider} from "~/functions/dataTable/useClientItemProvider.ts";

export function useQueryItemProvider<Row extends DataTableRow = DataTableRow>(
    query: UseQueryReturnType<Row[], DefaultError>,
    setContextFn?: (ctx: DataTableFilterContext) => void,
    refreshFn?: (flushCache: boolean) => Promise<void>
): DataTableItemProvider<Row> {
    const rows = computed(() => {
        return query.data?.value ?? [];
    });

    const loading = computed<boolean>(() => {
        return query.isFetching.value;
    });

    const setContext = (ctx: DataTableFilterContext): void => {
        if (typeof setContextFn === 'function') {
            setContextFn(ctx);
        }
    }

    const refresh = async (flushCache: boolean = false): Promise<void> => {
        if (typeof refreshFn === 'function') {
            await refreshFn(flushCache);
        } else {
            await query.refetch();
        }
    }

    return useClientItemProvider(
        rows,
        loading,
        setContext,
        refresh
    );
}
