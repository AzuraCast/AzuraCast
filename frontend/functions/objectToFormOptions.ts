import {map} from 'lodash';
import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";

export interface FormOption {
    value: string | number,
    text: string,
    description?: string
}

type SimpleFormOptionObject = Record<(string | number), string>

export type SimpleFormOptionInput = FormOption[] | SimpleFormOptionObject

export function objectToSimpleFormOptions(
    initial: MaybeRefOrGetter<SimpleFormOptionInput>,
): ComputedRef<FormOption[]> {
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

export interface FormOptionGroup {
    options: FormOption[],
    label: string,
}

type NestedFormOptionObject = SimpleFormOptionObject | Record<(string | number), SimpleFormOptionObject>

export type NestedFormOptionInput = (FormOption | FormOptionGroup)[] | NestedFormOptionObject;
export type NestedFormOptionOutput = (FormOption | FormOptionGroup)[];

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
