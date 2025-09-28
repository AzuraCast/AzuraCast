import {map} from "es-toolkit/compat";
import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";
import {ApiFormNestedOptions, ApiFormSimpleOptions} from "~/entities/ApiInterfaces.ts";

type SimpleFormOptionObject = Record<(string | number), string>

export type SimpleFormOptionInput = ApiFormSimpleOptions | SimpleFormOptionObject

export function objectToSimpleFormOptions(
    initial: MaybeRefOrGetter<SimpleFormOptionInput>,
): ComputedRef<ApiFormSimpleOptions> {
    return computed(() => {
        const array = toValue(initial);

        if (Array.isArray(array)) {
            return array;
        }

        return map(array, (outerValue, outerKey) => ({
            text: outerValue,
            value: outerKey
        }));
    });
}

type NestedFormOptionObject = SimpleFormOptionObject | Record<(string | number), SimpleFormOptionObject>

export type NestedFormOptionInput = ApiFormNestedOptions | NestedFormOptionObject;

export function objectToNestedFormOptions(
    initial: MaybeRefOrGetter<NestedFormOptionInput>
): ComputedRef<ApiFormNestedOptions> {
    return computed(() => {
        const array = toValue(initial);

        if (Array.isArray(array)) {
            return array;
        }

        return map(array, (outerValue, outerKey) => {
            // Support "optgroup" nested arrays
            if (typeof outerValue === 'object') {
                return {
                    label: outerKey,
                    options: map(outerValue, (innerValue, innerKey) => {
                        return {
                            text: innerValue,
                            value: innerKey
                        };
                    })
                };
            } else {
                return {
                    text: outerValue,
                    value: outerKey
                };
            }
        });
    });
}
