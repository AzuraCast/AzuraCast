import {useAsyncState} from "@vueuse/core";
import syncOnce from "~/functions/syncOnce";

/**
 * Just like useAsyncState, except with settings changed:
 *  - Does not reset to initial state after every reload
 *  - Only sets the "loading" ref to true on the initial load, not refreshes
 *
 * @see useAsyncState
 */
export default function useRefreshableAsyncState(
    promise,
    initialState,
    options = {}
) {
    const {state, isLoading: allIsLoading, execute} = useAsyncState(
        promise,
        initialState,
        {
            resetOnExecute: false,
            ...options
        }
    );

    const isLoading = syncOnce(allIsLoading);

    return {
        state,
        isLoading,
        execute
    };
}
