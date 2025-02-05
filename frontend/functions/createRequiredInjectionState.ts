import {createInjectionState, CreateInjectionStateOptions} from "@vueuse/shared";

export default function createRequiredInjectionState<Arguments extends Array<any>, Return>(
    composable: (...args: Arguments) => Return,
    options?: CreateInjectionStateOptions<Return>
): readonly [useProvidingState: (...args: Arguments) => Return, useInjectedState: () => Return] {
    const [useProvidingState, useInjectedStateOriginal] = createInjectionState(
        composable,
        options
    );

    const useInjectedState = () => {
        const state = useInjectedStateOriginal();
        if (!state) {
            throw new Error("Missing injection state!");
        }
        return state;
    }

    return [useProvidingState, useInjectedState];
}

