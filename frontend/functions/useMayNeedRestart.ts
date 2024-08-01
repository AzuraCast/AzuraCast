import {useEventBus} from "@vueuse/core";

export function useRestartEventBus() {
    return useEventBus<boolean>('station-restart');
}

export function useMayNeedRestart() {
    const eventBus = useRestartEventBus();

    const mayNeedRestart = () => {
        eventBus.emit(false);
    }

    return {
        mayNeedRestart
    }
}


