<template>
    <i :class="iconClass" aria-hidden="true">{{ iconContent }}</i>
</template>

<script>
export default {
    name: 'Icon',
    props: {
        type: {
            type: String,
            default: 'md'
        },
        icon: {
            type: String,
            required: true
        }
    },
    computed: {
        iconClass () {
            if (this.type === 'md') {
                return ['material-icons'];
            }
            return null;
        },
        iconContent () {
            return this.icon;
        }
    }
};
</script>
