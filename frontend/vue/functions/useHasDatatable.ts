import DataTable from "~/components/Common/DataTable.vue";
import {Ref} from "vue";

export type DataTableTemplateRef = InstanceType<typeof DataTable> | null;

export default function useHasDatatable($datatableRef: Ref<DataTableTemplateRef>) {
    const refresh = () => {
        return $datatableRef.value?.refresh();
    };

    const relist = () => {
        return $datatableRef.value?.relist();
    }

    const navigate = () => {
        return $datatableRef.value?.navigate();
    }

    const setFilter = (newTerm: string) => {
        return $datatableRef.value?.setFilter(newTerm);
    }

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
