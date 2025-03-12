import {inject, InjectionKey} from "vue";

export default function injectRequired<T>(key: InjectionKey<T>, defaultValue?: T): T {
    const resolved = inject(key, defaultValue);
    if (!resolved) {
        throw new Error("Key was not provided.");
    }
    return resolved;
}
