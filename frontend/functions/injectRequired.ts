import {InjectionKey} from "vue";
import {injectLocal} from "@vueuse/core";

export default function injectRequired<T>(key: InjectionKey<T>, defaultValue?: T): T {
    const resolved = injectLocal(key, defaultValue);
    if (!resolved) {
        throw new Error("Key was not provided.");
    }
    return resolved;
}
