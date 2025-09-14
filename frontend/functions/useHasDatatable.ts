import DataTable from "~/components/Common/DataTable.vue";
import {ComputedRef, ShallowRef} from "vue";
import {ComponentExposed} from "vue-component-type-helpers";

export type DataTableRow = Record<string, any>

export type DataTableTemplateRef<Row extends DataTableRow = DataTableRow> = ComponentExposed<
    typeof DataTable<Row>
>;

export type DataTableFilterContext = {
    searchPhrase: string,
    currentPage: number,
    sortField: string | null,
    sortOrder: string | null,
    paginated: boolean,
    perPage: number,
};

export const DATATABLE_DEFAULT_CONTEXT: DataTableFilterContext = {
    searchPhrase: '',
    currentPage: 1,
    sortField: null,
    sortOrder: null,
    paginated: false,
    perPage: 10,
};

export type DataTableItemProvider<Row extends DataTableRow = DataTableRow> = {
    rows: ComputedRef<Row[]>,
    total: ComputedRef<number>,
    loading: ComputedRef<boolean>,
    setContext: (ctx: DataTableFilterContext) => void,
    refresh: (flushCache?: boolean) => Promise<void>,
};

export default function useHasDatatable<Row extends DataTableRow = DataTableRow>(
    $datatableRef: Readonly<ShallowRef<DataTableTemplateRef<Row> | null>>
) {
    /**
     * Reset selected rows, active row, and trigger data reload.
     */
    const refresh = () => {
        return $datatableRef.value?.refresh();
    };

    /**
     * Refresh, but clearing the cache where relevant.
     * @see refresh
     */
    const relist = () => {
        return $datatableRef.value?.relist();
    }

    /**
     * Clear search phrase and current page, then call refresh().
     * @see relist
     */
    const navigate = () => {
        return $datatableRef.value?.navigate();
    }

    /**
     * Set the current search filer string.
     * @param newTerm The new search term.
     */
    const setFilter = (newTerm: string) => {
        return $datatableRef.value?.setFilter(newTerm);
    }

    return {
        refresh,
        relist,
        navigate,
        setFilter
    };
}
