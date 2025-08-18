import {computed, MaybeRefOrGetter, toValue} from "vue";
import {RegleValidationGroupOutput} from "@regle/core";

export const useFormTabClass = (r$: MaybeRefOrGetter<RegleValidationGroupOutput>) => {
    return computed(() => {
        const {$dirty, $invalid} = toValue(r$);

        if ($dirty && $invalid) {
            return 'text-danger';
        }
        return '';
    });
}
