import {useQueryClient} from "@tanstack/vue-query";
import {queryKeyWithStation} from "~/entities/Queries.ts";
import {useStationId} from "~/functions/useStationQuery.ts";

export function useMayNeedRestart() {
    const queryClient = useQueryClient();
    const stationId = useStationId();

    const mayNeedRestart = () => {
        void queryClient.invalidateQueries({
            queryKey: queryKeyWithStation([], stationId)
        });
    }

    return {
        mayNeedRestart
    }
}


