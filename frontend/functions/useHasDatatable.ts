import DataTable from "~/components/Common/DataTable.vue";
import {ShallowRef} from "vue";
import {ComponentExposed} from "vue-component-type-helpers";

export type DataTableTemplateRef = ComponentExposed<typeof DataTable>;

export default function useHasDatatable($datatableRef: Readonly<ShallowRef<DataTableTemplateRef | null>>) {
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
