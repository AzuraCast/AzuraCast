import {inject, provide, watch} from "vue";

const injectKey = 'webDjPassthroughSync';

export function useProvidePassthroughSync (passthroughSync) {
    provide(injectKey, passthroughSync);
}

export function useInjectPassthroughSync() {
    return inject(injectKey);
}

export function usePassthroughSync(thisPassthrough, stringVal) {
    const passthroughSync = useInjectPassthroughSync();

    watch(passthroughSync, (newVal) => {
        if (newVal !== stringVal) {
            thisPassthrough.value = false;
        }
    });

    watch(thisPassthrough, (newVal) => {
        if (newVal) {
            passthroughSync.value = stringVal;
        }
    });
}
