import useVuelidate from "@vuelidate/core";
import {computed} from "vue";

export function useVuelidateOnFormTab(validations, form, options = {}) {
    const v$ = useVuelidate(validations, form, options);

    const isValid = computed(() => {
        return !v$.value.$invalid ?? true;
    });

    const tabClass = computed(() => {
        if (v$.value.$anyDirty && v$.value.$invalid) {
            return 'text-danger';
        }
        return null;
    });

    return {
        v$,
        isValid,
        tabClass
    };
}
