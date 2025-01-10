import DataTable from "~/components/Common/DataTable.vue";
import {Ref} from "vue";
import {ComponentExposed} from "vue-component-type-helpers";

export type DataTableTemplateRef = ComponentExposed<typeof DataTable> | null;

export default function useHasDatatable($datatableRef: Ref<DataTableTemplateRef>) {
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

    /**
     * Either set the specified row as active, or disable it if it already is active.
     * @param row
     */
    const toggleDetails = (row) => {
        return $datatableRef.value?.toggleDetails(row);
    };

    return {
        refresh,
        relist,
        navigate,
        setFilter,
        toggleDetails
    };
}
