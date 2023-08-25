import DataTable from "~/components/Common/DataTable.vue";
import {Ref} from "vue";

export type DataTableTemplateRef = InstanceType<typeof DataTable> | null;

export default function useHasDatatable($datatableRef: Ref<DataTableTemplateRef>) {
    const relist = () => {
        return $datatableRef.value?.relist();
    }

    return {
        relist
    };
}
