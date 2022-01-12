import _ from 'lodash';

export default function objectToFormOptions(array) {
    return _.map(array, (outerValue, outerKey) => {
        // Support "outgroup" nested arrays
        if (typeof outerValue === 'object') {
            return {
                label: outerKey,
                options: _.map(outerValue, (innerValue, innerKey) => {
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
