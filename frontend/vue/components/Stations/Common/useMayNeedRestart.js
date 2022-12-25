import {useAxios} from "~/vendor/axios";

export const mayNeedRestartProps = {
    restartStatusUrl: String
};

export function useNeedsRestart() {
    const needsRestart = () => {
        document.dispatchEvent(new CustomEvent("station-needs-restart"));
    }

    return {
        needsRestart
    };
}

export function useMayNeedRestart(restartStatusUrl) {
    const {needsRestart} = useNeedsRestart();
    const {axios} = useAxios();

    const mayNeedRestart = () => {
        axios.get(restartStatusUrl).then((resp) => {
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


