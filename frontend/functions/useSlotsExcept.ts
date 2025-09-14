import {filter, includes} from "es-toolkit/compat";
import {computed, useSlots} from "vue";

export default function useSlotsExcept(except: string[]) {
    const slots = useSlots();

    return computed(() => {
        return filter(slots, (_, name: string) => {
            return !includes(except, name);
        });
    });
}
