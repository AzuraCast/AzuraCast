import {computed, MaybeRef, unref} from "vue";
import {RegleCommonStatus, RegleValidationGroupOutput} from "@regle/core";

export const useFormTabClass = (r$: MaybeRef<RegleValidationGroupOutput | RegleCommonStatus>) => {
    return computed(() => {
        const {$dirty, $invalid} = unref(r$);

        if ($dirty && $invalid) {
            return 'text-danger';
        }
        return '';
    });
}
