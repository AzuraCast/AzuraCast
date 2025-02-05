import {Ref, ref, watch} from "vue";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

const [useProvidePassthroughSync, useInjectPassthroughSync] = createRequiredInjectionState(
    (initialValue: string) => {
        const passThroughSync = ref<string>(initialValue);
        return {passThroughSync};
    }
);

export {useProvidePassthroughSync};

export function usePassthroughSync(
    thisPassThrough: Ref<boolean>,
    stringVal: string
) {
    const {passThroughSync} = useInjectPassthroughSync();

    watch(passThroughSync, (newVal) => {
        if (newVal !== stringVal) {
            thisPassThrough.value = false;
        }
    });

    watch(thisPassThrough, (newVal) => {
        if (newVal) {
            passThroughSync.value = stringVal;
        }
    });
}
