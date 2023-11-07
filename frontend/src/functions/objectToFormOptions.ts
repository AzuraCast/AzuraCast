import {map} from 'lodash';

export default function objectToFormOptions(array) {
    return map(array, (outerValue, outerKey) => {
        // Support "outgroup" nested arrays
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
}
