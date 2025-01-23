import {useAsyncState, UseAsyncStateOptions, UseAsyncStateReturn} from "@vueuse/core";
import syncOnce from "~/functions/syncOnce";

/**
 * Just like useAsyncState, except with settings changed:
 *  - Does not reset to initial state after every reload
 *  - Only sets the "loading" ref to true on the initial load, not refreshes
 *
 * @see useAsyncState
 */
export default function useRefreshableAsyncState<Data, Params extends any[] = [], Shallow extends boolean = true>(
    promise: Promise<Data> | ((...args: Params) => Promise<Data>),
    initialState: Data,
    options: UseAsyncStateOptions<Shallow, Data> = {}
): UseAsyncStateReturn<Data, Params, Shallow> {
    const parentState = useAsyncState(
        promise,
        initialState,
        {
            resetOnExecute: false,
            ...options
        }
    );

    const parentIsLoading = parentState.isLoading;
    parentState.isLoading = syncOnce(parentIsLoading);

    return parentState;
}
