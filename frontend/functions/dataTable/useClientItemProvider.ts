import {
    DATATABLE_DEFAULT_CONTEXT,
    DataTableFilterContext,
    DataTableItemProvider,
    DataTableRow
} from "~/functions/useHasDatatable.ts";
import {computed, MaybeRefOrGetter, shallowRef, toValue} from "vue";
import {filter, get, slice} from "es-toolkit/compat";
import {useAzuraCast} from "~/vendor/azuracast.ts";

export function useClientItemProvider<Row extends DataTableRow = DataTableRow>(
    items: MaybeRefOrGetter<Row[]>,
    isLoading?: MaybeRefOrGetter<boolean>,
    setContextFn?: (ctx: DataTableFilterContext) => void,
    refreshFn?: (flushCache: boolean) => Promise<void>
): DataTableItemProvider<Row> {
    const context = shallowRef<DataTableFilterContext>(DATATABLE_DEFAULT_CONTEXT);

    const setContext = (ctx: DataTableFilterContext) => {
        context.value = ctx;

        if (typeof setContextFn === 'function') {
            setContextFn(ctx);
        }
    }

    const filteredItems = computed<Row[]>(() => {
        const searchPhrase = context.value.searchPhrase.toLowerCase();

        return filter(toValue(items), (item) =>
            Object.entries(item).filter((item) => {
                const [key, val] = item;
                if (!val || key[0] === '_') {
                    return false;
                }

                const itemValue = typeof val === 'object'
                    ? JSON.stringify(Object.values(val))
                    : typeof val === 'string'
                        ? val : val.toString();

                return itemValue.toLowerCase().includes(searchPhrase)
            }).length > 0
        );
    });

    const total = computed(() => {
        return filteredItems.value.length;
    });

    const {localeShort} = useAzuraCast();

    const rows = computed(() => {
        let itemsOnPage = filteredItems.value;

        const { sortField, sortOrder } = context.value;

        if (sortField !== null) {
            const collator = new Intl.Collator(localeShort, {numeric: true, sensitivity: 'base'});

            itemsOnPage = itemsOnPage.sort(
                (a, b) => collator.compare(
                    get(a, sortField, ''),
                    get(b, sortField, '')
                )
            );

            if (sortOrder === 'desc') {
                itemsOnPage = itemsOnPage.reverse();
            }
        }

        // Handle pagination client-side.
        if (context.value.paginated && context.value.perPage > 0) {
            itemsOnPage = slice(
                itemsOnPage,
                (context.value.currentPage - 1) * context.value.perPage,
                context.value.currentPage * context.value.perPage
            );
        }

        return itemsOnPage;
    });

    const loading = computed(() => {
        return (isLoading !== undefined)
            ? toValue(isLoading)
            : false;
    });

    const refresh = async (flushCache: boolean = false): Promise<void> => {
        if (typeof refreshFn === 'function') {
            await refreshFn(flushCache);
        }
    }

    return {
        rows,
        total,
        loading,
        setContext,
        refresh
    }
}
