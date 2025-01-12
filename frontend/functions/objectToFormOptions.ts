import {map} from 'lodash';
import {computed, ComputedRef, toValue} from "vue";

export interface FormOption {
    value: any,
    text: string,
    description?: string
}

export interface FormOptionGroup {
    options: FormOption[],
    label: string,
}

export type FormOptionInput = (FormOption | FormOptionGroup)[] | Record<string, any>;
export type FormOptionOutput = (FormOption | FormOptionGroup)[];

export default function objectToFormOptions(
    initial: MaybeRefOrGetter<FormOptionInput>
): ComputedRef<FormOptionOutput> {
    return computed(() => {
        const array: FormOptionInput = toValue(initial);

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
