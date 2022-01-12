<template>
    <div style="display: contents">
        <div v-for="message in errorMessages">
            {{ message }}
        </div>
    </div>
</template>

<script>
import _ from 'lodash';

export default {
    name: 'VuelidateError',
    props: {
        field: Object
    },
    data() {
        return {
            messages: {
                required: () => {
                    return this.$gettext('This field is required.');
                },
                minLength: (params) => {
                    let text = this.$gettext('This field must have at least %{ min } letters.');
                    return this.$gettextInterpolate(text, params);
                },
                maxLength: (params) => {
                    let text = this.$gettext('This field must have at most %{ max } letters.');
                    return this.$gettextInterpolate(text, params);
                },
                between: (params) => {
                    let text = this.$gettext('This field must be between %{ min } and %{ max }.');
                    return this.$gettextInterpolate(text, params);
                },
                alpha: () => {
                    return this.$gettext('This field must only contain alphabetic characters.');
                },
                alphaNum: () => {
                    return this.$gettext('This field must only contain alphanumeric characters.');
                },
                numeric: () => {
                    return this.$gettext('This field must only contain numeric characters.');
                },
                integer: () => {
                    return this.$gettext('This field must be a valid integer.');
                },
                decimal: () => {
                    return this.$gettext('This field must be a valid decimal number.');
                },
                email: () => {
                    return this.$gettext('This field must be a valid e-mail address.');
                },
                ipAddress: () => {
                    return this.$gettext('This field must be a valid IP address.');
                },
                url: () => {
                    return this.$gettext('This field must be a valid URL.');
                },
                validatePassword: () => {
                    return this.$gettext('This password is too common or insecure.');
                }
            }
        }
    },
    computed: {
        errorMessages() {
            if (!this.field.$error) {
                return [];
            }

            let errors = [];
            _.forEach(this.messages, (message, key) => {
                const isValid = !!_.get(this.field, key, true);
                if (!isValid) {
                    const params = _.get(this.field, ['$params', key], {});
                    errors.push(message(params));
                }
            });

            return errors;
        }
    }
}
</script>
