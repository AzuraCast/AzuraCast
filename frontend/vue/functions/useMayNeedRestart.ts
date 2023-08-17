import {useEventBus} from "@vueuse/core";

export function useMayNeedRestart() {
    const eventBus = useEventBus<boolean>('station-restart');

    const needsRestart = () => {
        eventBus.emit(true);
    }

    const mayNeedRestart = () => {
        eventBus.emit(false);
    }

    return {
        needsRestart,
        mayNeedRestart
    }
}


