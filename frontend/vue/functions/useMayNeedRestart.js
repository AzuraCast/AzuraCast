import {useAxios} from "~/vendor/axios";
import {getStationApiUrl} from "~/router";

export function useNeedsRestart() {
    const needsRestart = () => {
        document.dispatchEvent(new CustomEvent("station-needs-restart"));
    }

    return {
        needsRestart
    };
}

export function useMayNeedRestart() {
    const restartStatusUrl = getStationApiUrl('/restart-status');

    const {needsRestart} = useNeedsRestart();
    const {axios} = useAxios();

    const mayNeedRestart = () => {
        axios.get(restartStatusUrl.value).then((resp) => {
            if (resp.data.needs_restart) {
                needsRestart();
            }
        });
    }

    return {
        needsRestart,
        mayNeedRestart
    }
}


