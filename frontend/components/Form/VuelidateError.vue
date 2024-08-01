<template>
    <div
        v-for="message in errorMessages"
        :key="message"
        class="invalid-feedback"
    >
        {{ message }}
    </div>
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext";
import {get, map} from "lodash";
import {computed} from "vue";

const props = defineProps({
    field: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const messages = {
    required: () => {
        return $gettext('This field is required.');
    },
    minLength: (params) => {
        return $gettext(
            'This field must have at least %{ min } letters.',
            params
        );
    },
    maxLength: (params) => {
        return $gettext(
            'This field must have at most %{ max } letters.',
            params
        );
    },
    between: (params) => {
        return $gettext(
            'This field must be between %{ min } and %{ max }.',
            params
        );
    },
    alpha: () => {
        return $gettext('This field must only contain alphabetic characters.');
    },
    alphaNum: () => {
        return $gettext('This field must only contain alphanumeric characters.');
    },
    numeric: () => {
        return $gettext('This field must only contain numeric characters.');
    },
    integer: () => {
        return $gettext('This field must be a valid integer.');
    },
    decimal: () => {
        return $gettext('This field must be a valid decimal number.');
    },
    email: () => {
        return $gettext('This field must be a valid e-mail address.');
    },
    ipAddress: () => {
        return $gettext('This field must be a valid IP address.');
    },
    url: () => {
        return $gettext('This field must be a valid URL.');
    },
    validatePassword: () => {
        return $gettext('This password is too common or insecure.');
    }
};

const errorMessages = computed(() => {
    return map(
        props.field.$errors,
        (error) => {
            const message = get(messages, error.$validator, null);
            if (null !== message) {
                return message(error.$params);
            } else {
                return error.$message;
            }
        }
    );
});
</script>
