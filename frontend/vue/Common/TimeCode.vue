<template>
    <b-input v-bind="$attrs" type="time" v-model="timeCode" pattern="[0-9]{2}:[0-9]{2}" placeholder="13:45"></b-input>
</template>

<script>
import _ from 'lodash';

export default {
    props: ['value'],
    computed: {
        timeCode: {
            get () {
                return this.parseTimeCode(this.value);
            },
            set (newValue) {
                this.$emit('input', this.convertToTimeCode(newValue));
            }
        }
    },
    methods: {
        parseTimeCode (timeCode) {
            if (timeCode !== '' && timeCode !== null) {
                timeCode = _.padStart(timeCode, 4, '0');
                return timeCode.substr(0, 2) + ':' + timeCode.substr(2);
            }

            return null;
        },
        convertToTimeCode (time) {
            if (_.isEmpty(time)) {
                return null;
            }

            let timeParts = time.split(':');
            return (100 * parseInt(timeParts[0], 10)) + parseInt(timeParts[1], 10);
        }
    }
};
</script>
