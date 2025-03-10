import {map} from "lodash";
import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";
import {ApiFormOption, ApiFormOptionGroup} from "~/entities/ApiInterfaces.ts";

type SimpleFormOptionObject = Record<(string | number), string>

export type SimpleFormOptionInput = ApiFormOption[] | SimpleFormOptionObject

export function objectToSimpleFormOptions(
    initial: MaybeRefOrGetter<SimpleFormOptionInput>,
): ComputedRef<ApiFormOption[]> {
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

export type NestedFormOptionInput = (ApiFormOption | ApiFormOptionGroup)[] | NestedFormOptionObject;
export type NestedFormOptionOutput = (ApiFormOption | ApiFormOptionGroup)[];

export function objectToNestedFormOptions(
    initial: MaybeRefOrGetter<NestedFormOptionInput>
): ComputedRef<NestedFormOptionOutput> {
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
