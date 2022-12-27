import {filter, includes} from "lodash";
import {computed, useSlots} from "vue";

export default function useSlotsExcept(except) {
    const slots = useSlots();

    return computed(() => {
        return filter(slots, (slot, name) => {
            return !includes(except, name);
        });
    });
};
