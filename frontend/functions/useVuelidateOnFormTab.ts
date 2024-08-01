import useVuelidate from "@vuelidate/core";
import {computed} from "vue";
import {useEventBus} from "@vueuse/core";

export function useVuelidateOnFormTab(validations, form, blankForm = {}, vuelidateOptions = {}) {
    const v$ = useVuelidate(validations, form, vuelidateOptions);

    const isValid = computed(() => {
        return !v$.value.$invalid;
    });

    const tabClass = computed(() => {
        if (v$.value.$anyDirty && v$.value.$invalid) {
            return 'text-danger';
        }
        return null;
    });

    // Register event listener for blankForm building.
    const formEventBus = useEventBus('form_tabs');

    formEventBus.on((addToForm) => {
        addToForm(blankForm);
    });

    return {
        v$,
        isValid,
        tabClass
    };
}
