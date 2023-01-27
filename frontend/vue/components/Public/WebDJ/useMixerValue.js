import {inject, provide} from "vue";

const injectKey = "webDjMixer";

export function useProvideMixer(mixer) {
    provide(injectKey, mixer);
}

export function useInjectMixer() {
    return inject(injectKey);
}
