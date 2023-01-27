import {useAxios} from "~/vendor/axios";
import {toRef} from "vue";

export const mayNeedRestartProps = {
    restartStatusUrl: {
        type: String,
        required: true
    }
};

export function useNeedsRestart() {
    const needsRestart = () => {
        document.dispatchEvent(new CustomEvent("station-needs-restart"));
    }

    return {
        needsRestart
    };
}

export function useMayNeedRestart(props) {
    const restartStatusUrl = toRef(props, 'restartStatusUrl');

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


